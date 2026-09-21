<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WidgetAreaResource;
use App\Models\WidgetArea;

class WidgetAreaController extends Controller
{
    public function show(string $key)
    {
        $area = WidgetArea::where('key', $key)
            ->with(['activeBlocks' => fn ($q) => $q->orderByPivot('order')])
            ->firstOrFail();

        return new WidgetAreaResource($area);
    }
}
