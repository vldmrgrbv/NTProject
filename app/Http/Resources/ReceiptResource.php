<?php

namespace App\Http\Resources;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Receipt
 */
class ReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'fn' => $this->fn,
            'fd' => $this->fd,
            'fp' => $this->fp,
            'dt' => $this->dt,
            'sum' => $this->sum,
            'inn' => $this->inn,
            'scores' => $this->scores,
            'nt_number' => $this->nt_number,
            'skus' => $this->skus,
            'source' => $this->source,
            'qr_string' => $this->qr_string,
            'created_at' => $this->created_at,
        ];
    }
}
