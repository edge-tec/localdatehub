import { ethers } from 'ethers';

async function readStdin() {
    const chunks = [];
    for await (const chunk of process.stdin) {
        chunks.push(chunk);
    }
    return JSON.parse(Buffer.concat(chunks).toString('utf8'));
}

function resolveWallet(secret, index) {
    const trimmed = String(secret || '').trim();
    if (!trimmed) {
        throw new Error('Empty master secret.');
    }

    // Single EVM private key (64 hex chars, with or without 0x)
    const pkMatch = trimmed.match(/^(0x)?([a-fA-F0-9]{64})$/);
    if (pkMatch) {
        return {
            wallet: new ethers.Wallet('0x' + pkMatch[2]),
            path: 'direct-private-key',
        };
    }

    // BIP39 Mnemonic phrase (12 / 15 / 18 / 21 / 24 words)
    const words = trimmed.split(/\s+/).filter(Boolean);
    if (words.length >= 12) {
        const path = `m/44'/60'/0'/0/${Number(index) || 0}`;
        const wallet = ethers.HDNodeWallet.fromPhrase(words.join(' '), undefined, path);
        return { wallet, path };
    }

    throw new Error('Secret must be a 12/24-word seed phrase or a 64-character hex private key.');
}

async function handleDerive(input) {
    const { wallet, path } = resolveWallet(input.secret, input.index ?? 0);
    return {
        success: true,
        address: wallet.address,
        path,
    };
}

async function getWorkingProvider(rpcUrls) {
    const urls = Array.isArray(rpcUrls) ? rpcUrls.filter(Boolean) : [rpcUrls].filter(Boolean);
    let lastError = null;

    for (const url of urls) {
        try {
            const provider = new ethers.JsonRpcProvider(url, undefined, { staticNetwork: false });
            await Promise.race([
                provider.getBlockNumber(),
                new Promise((_, reject) => setTimeout(() => reject(new Error('RPC timeout')), 6000)),
            ]);
            return provider;
        } catch (err) {
            lastError = err;
        }
    }

    throw new Error(`All RPC endpoints failed: ${lastError?.message || 'unknown error'}`);
}

async function handleSweep(input) {
    const { wallet } = resolveWallet(input.secret, input.index ?? 0);
    const provider = await getWorkingProvider(input.rpc_urls);
    const connectedWallet = wallet.connect(provider);
    const toAddress = ethers.getAddress(input.to_address);

    if (connectedWallet.address.toLowerCase() === toAddress.toLowerCase()) {
        return {
            success: true,
            tx_hash: null,
            already_in_destination: true,
            amount_swept: '0',
            gas_cost: '0',
        };
    }

    const contractAddress = input.contract_address ? String(input.contract_address).trim() : '';

    if (!contractAddress) {
        // Native coin sweep (BNB, ETH, MATIC)
        const balance = await provider.getBalance(connectedWallet.address);
        if (balance <= 0n) {
            throw new Error(`Zero native balance on ${connectedWallet.address}`);
        }

        const feeData = await provider.getFeeData();
        const gasPrice = feeData.gasPrice || ethers.parseUnits('3', 'gwei');
        const gasLimit = 21000n;
        const gasCost = gasPrice * gasLimit;
        const sendValue = balance - gasCost;

        if (sendValue <= 0n) {
            throw new Error(`Balance (${ethers.formatEther(balance)}) is too low to cover gas (${ethers.formatEther(gasCost)}).`);
        }

        const tx = await connectedWallet.sendTransaction({
            to: toAddress,
            value: sendValue,
            gasLimit,
            gasPrice,
        });

        return {
            success: true,
            tx_hash: tx.hash,
            amount_swept: ethers.formatEther(sendValue),
            gas_cost: ethers.formatEther(gasCost),
        };
    }

    // ERC20 / BEP20 Token sweep
    const abi = [
        'function balanceOf(address owner) view returns (uint256)',
        'function decimals() view returns (uint8)',
        'function transfer(address to, uint256 value) returns (bool)',
    ];
    const token = new ethers.Contract(ethers.getAddress(contractAddress), abi, connectedWallet);
    const [tokenBalance, decimals, feeData, nativeBalance] = await Promise.all([
        token.balanceOf(connectedWallet.address),
        token.decimals().catch(() => 18),
        provider.getFeeData(),
        provider.getBalance(connectedWallet.address),
    ]);

    if (tokenBalance <= 0n) {
        throw new Error(`Zero token balance on ${connectedWallet.address}`);
    }

    const gasPrice = feeData.gasPrice || ethers.parseUnits('3', 'gwei');
    const estimatedGas = await token.transfer.estimateGas(toAddress, tokenBalance).catch(() => 65000n);
    const neededGas = estimatedGas * gasPrice * 120n / 100n;

    // If deposit address lacks native gas, try topping up gas from master index 0 (if mnemonic)
    if (nativeBalance < neededGas && (Number(input.index) || 0) > 0) {
        try {
            const { wallet: gasWallet } = resolveWallet(input.secret, 0);
            const connectedGasWallet = gasWallet.connect(provider);
            const gasWalletBal = await provider.getBalance(connectedGasWallet.address);
            const topUpAmount = neededGas - nativeBalance + (gasPrice * 5000n);
            if (gasWalletBal > topUpAmount + (gasPrice * 21000n)) {
                const fundTx = await connectedGasWallet.sendTransaction({
                    to: connectedWallet.address,
                    value: topUpAmount,
                    gasLimit: 21000n,
                    gasPrice,
                });
                await fundTx.wait(1);
            }
        } catch {
            // Proceed to let transfer attempt or throw clear gas error
        }
    }

    const tx = await token.transfer(toAddress, tokenBalance, {
        gasLimit: estimatedGas * 120n / 100n,
        gasPrice,
    });

    return {
        success: true,
        tx_hash: tx.hash,
        amount_swept: ethers.formatUnits(tokenBalance, decimals),
        gas_cost: ethers.formatEther(estimatedGas * gasPrice),
    };
}

try {
    const input = await readStdin();
    const result = input.action === 'sweep'
        ? await handleSweep(input)
        : await handleDerive(input);
    process.stdout.write(JSON.stringify(result));
} catch (error) {
    process.stdout.write(JSON.stringify({
        success: false,
        error: error?.message || String(error),
    }));
    process.exitCode = 1;
}
