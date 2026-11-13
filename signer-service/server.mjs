import http from 'node:http';
import crypto from 'node:crypto';
import { ethers } from 'ethers';

const PORT = Number(process.env.SIGNER_PORT || 8590);
const HOST = process.env.SIGNER_HOST || '127.0.0.1';
const HMAC_SECRET = String(process.env.SIGNER_HMAC_SECRET || '');
const DEPOSIT_MNEMONIC = String(process.env.SIGNER_DEPOSIT_MNEMONIC || '');
const GAS_SPONSOR_PK = String(process.env.SIGNER_GAS_SPONSOR_PK || '');
const EMERGENCY_SHUTDOWN = String(process.env.SIGNER_EMERGENCY_SHUTDOWN || 'false') === 'true';
const ALLOWED_TREASURIES = new Set(
    String(process.env.SIGNER_ALLOWED_TREASURIES || '')
        .split(',')
        .map((s) => s.trim().toLowerCase())
        .filter(Boolean)
);
const ALLOWED_CHAINS = new Set(
    String(process.env.SIGNER_ALLOWED_CHAINS || '11155111,97')
        .split(',')
        .map((s) => Number(s.trim()))
        .filter((n) => Number.isFinite(n) && n > 0)
);

const seenNonces = new Map();
const MAX_SKEW_SECONDS = 60;

function cleanupNonces() {
    const cutoff = Date.now() - MAX_SKEW_SECONDS * 1000;
    for (const [nonce, ts] of seenNonces.entries()) {
        if (ts < cutoff) seenNonces.delete(nonce);
    }
}

function verifyHmac(req, rawBody) {
    if (!HMAC_SECRET) {
        return { ok: false, status: 503, error: 'SIGNER_HMAC_SECRET not configured' };
    }
    const ts = String(req.headers['x-custody-timestamp'] || '');
    const nonce = String(req.headers['x-custody-nonce'] || '');
    const sig = String(req.headers['x-custody-signature'] || '');
    if (!ts || !nonce || !sig) {
        return { ok: false, status: 401, error: 'Missing HMAC authentication headers' };
    }
    const nowSec = Math.floor(Date.now() / 1000);
    if (Math.abs(nowSec - Number(ts)) > MAX_SKEW_SECONDS) {
        return { ok: false, status: 401, error: 'Stale request timestamp' };
    }
    cleanupNonces();
    if (seenNonces.has(nonce)) {
        return { ok: false, status: 409, error: 'Replay nonce detected' };
    }
    const urlPath = (req.url || '/').split('?')[0];
    const canonical = `${ts}.${nonce}.${req.method}.${urlPath}.${rawBody}`;
    const expected = crypto.createHmac('sha256', HMAC_SECRET).update(canonical).digest('hex');
    const a = Buffer.from(sig, 'utf8');
    const b = Buffer.from(expected, 'utf8');
    if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) {
        return { ok: false, status: 403, error: 'Invalid HMAC signature' };
    }
    seenNonces.set(nonce, Date.now());
    return { ok: true };
}

function deriveEvmWallet(index) {
    if (!DEPOSIT_MNEMONIC) {
        throw new Error('SIGNER_DEPOSIT_MNEMONIC is not configured in isolated signer.');
    }
    const path = `m/44'/60'/0'/0/${Number(index)}`;
    return ethers.HDNodeWallet.fromPhrase(DEPOSIT_MNEMONIC.trim(), undefined, path);
}

async function handleRequest(req, res) {
    const chunks = [];
    for await (const chunk of req) chunks.push(chunk);
    const rawBody = Buffer.concat(chunks).toString('utf8');

    const auth = verifyHmac(req, rawBody);
    if (!auth.ok) {
        res.writeHead(auth.status, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ ok: false, error: auth.error }));
    }

    const path = (req.url || '/').split('?')[0];
    const body = rawBody ? JSON.parse(rawBody) : {};

    try {
        if (req.method === 'GET' && path === '/v1/health') {
            res.writeHead(200, { 'Content-Type': 'application/json' });
            return res.end(JSON.stringify({
                ok: !EMERGENCY_SHUTDOWN,
                status: EMERGENCY_SHUTDOWN ? 'emergency_shutdown' : 'healthy',
                signer: 'isolated-self-hosted',
                mnemonic_configured: Boolean(DEPOSIT_MNEMONIC),
                gas_sponsor_configured: Boolean(GAS_SPONSOR_PK),
                allowed_chains: [...ALLOWED_CHAINS],
            }));
        }

        if (req.method === 'POST' && path === '/v1/addresses/derive') {
            const index = Number(body.index ?? 0);
            const wallet = deriveEvmWallet(index);
            res.writeHead(200, { 'Content-Type': 'application/json' });
            return res.end(JSON.stringify({ address: wallet.address, index }));
        }

        if (EMERGENCY_SHUTDOWN) {
            res.writeHead(423, { 'Content-Type': 'application/json' });
            return res.end(JSON.stringify({ ok: false, error: 'Emergency signing shutdown active' }));
        }

        if (req.method === 'POST' && path === '/v1/transactions/sign') {
            const chainId = Number(body.chain_id || 0);
            const toAddress = String(body.to_address || '').trim();
            if (!ALLOWED_CHAINS.has(chainId)) {
                res.writeHead(403, { 'Content-Type': 'application/json' });
                return res.end(JSON.stringify({ error: `Chain ID ${chainId} not allowlisted` }));
            }
            if (ALLOWED_TREASURIES.size > 0 && !ALLOWED_TREASURIES.has(toAddress.toLowerCase())) {
                res.writeHead(403, { 'Content-Type': 'application/json' });
                return res.end(JSON.stringify({ error: 'Destination treasury not allowlisted in signer' }));
            }

            const wallet = deriveEvmWallet(Number(body.derivation_index ?? 0));
            const contract = body.contract_address ? String(body.contract_address).trim() : null;
            const amount = BigInt(String(body.amount_base_units || '0'));
            const gasLimit = BigInt(String(body.gas_limit || (contract ? 65000 : 21000)));
            const maxFeePerGas = BigInt(String(body.max_fee_per_gas || '20000000000'));
            const maxPriorityFeePerGas = BigInt(String(body.max_priority_fee_per_gas || '1500000000'));
            const nonce = Number(body.nonce ?? 0);

            let txReq;
            if (contract) {
                const iface = new ethers.Interface(['function transfer(address to, uint256 value) returns (bool)']);
                txReq = {
                    type: 2,
                    chainId,
                    nonce,
                    to: contract,
                    value: 0n,
                    data: iface.encodeFunctionData('transfer', [toAddress, amount]),
                    gasLimit,
                    maxFeePerGas,
                    maxPriorityFeePerGas,
                };
            } else {
                txReq = {
                    type: 2,
                    chainId,
                    nonce,
                    to: toAddress,
                    value: amount,
                    gasLimit,
                    maxFeePerGas,
                    maxPriorityFeePerGas,
                };
            }

            const signedTx = await wallet.signTransaction(txReq);
            const txHash = ethers.keccak256(signedTx);

            res.writeHead(200, { 'Content-Type': 'application/json' });
            return res.end(JSON.stringify({
                signed_tx: signedTx,
                tx_hash: txHash,
                request_id: crypto.randomUUID(),
            }));
        }

        if (req.method === 'POST' && path === '/v1/gas/sponsor') {
            if (!GAS_SPONSOR_PK) {
                res.writeHead(503, { 'Content-Type': 'application/json' });
                return res.end(JSON.stringify({ error: 'SIGNER_GAS_SPONSOR_PK not configured' }));
            }
            const chainId = Number(body.chain_id || 0);
            if (!ALLOWED_CHAINS.has(chainId)) {
                res.writeHead(403, { 'Content-Type': 'application/json' });
                return res.end(JSON.stringify({ error: `Chain ID ${chainId} not allowlisted` }));
            }
            const sponsor = new ethers.Wallet(GAS_SPONSOR_PK.trim());
            const signedTx = await sponsor.signTransaction({
                type: 2,
                chainId,
                nonce: Number(body.nonce ?? 0),
                to: String(body.target_address),
                value: BigInt(String(body.amount_base_units || '0')),
                gasLimit: 21000n,
                maxFeePerGas: BigInt(String(body.max_fee_per_gas || '20000000000')),
                maxPriorityFeePerGas: BigInt(String(body.max_priority_fee_per_gas || '1500000000')),
            });
            res.writeHead(200, { 'Content-Type': 'application/json' });
            return res.end(JSON.stringify({
                signed_tx: signedTx,
                tx_hash: ethers.keccak256(signedTx),
                request_id: crypto.randomUUID(),
            }));
        }

        res.writeHead(404, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'Not found' }));
    } catch (err) {
        res.writeHead(500, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: err.message }));
    }
}

http.createServer(handleRequest).listen(PORT, HOST, () => {
    console.log(`Isolated HD Signer listening on http://${HOST}:${PORT}`);
});

