<?php

namespace App\Modules\Search\Requests;

use App\Modules\Search\DTOs\SearchFilterDTO;
use Illuminate\Foundation\Http\FormRequest;

class SearchFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'regex:/^[0-9]{5}$/'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'integer', 'min:1', 'max:150'],
            'min_age' => ['nullable', 'integer', 'min:18', 'max:99'],
            'max_age' => ['nullable', 'integer', 'min:18', 'max:99'],
            'verified_only' => ['nullable', 'boolean'],
            'with_photos' => ['nullable', 'boolean'],
            'featured_only' => ['nullable', 'boolean'],
            'published_date' => ['nullable', 'string', 'in:all,today,7d,30d'],
            'attr' => ['nullable', 'array'],
            'attr.*' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:newest,oldest,updated,views,latest'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toDTO(): SearchFilterDTO
    {
        return SearchFilterDTO::fromArray($this->validated());
    }
}

