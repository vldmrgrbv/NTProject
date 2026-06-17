<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Services\AnalyticsService;
use Illuminate\Support\Carbon;
use MoonShine\Laravel\Pages\Page;
use MoonShine\Support\Attributes\AsyncMethod;
use MoonShine\Support\Enums\FormMethod;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\ActionGroup;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Preview;
use Rap2hpoutre\FastExcel\FastExcel;
use MoonShine\Support\Enums\HttpMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsPage extends Page
{
    protected string $title = 'Аналитика';

    public function __construct(protected AnalyticsService $analyticsService)
    {
        parent::__construct(parent::getCore());
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            '#' => $this->getTitle(),
        ];
    }

    public function components(): array
    {
        $dateFrom = request('date_from') ? Carbon::parse(request('date_from')) : null;
        $dateTo = request('date_to') ? Carbon::parse(request('date_to')) : null;

        $analyticsData = $this->analyticsService->getAnalytics($dateFrom, $dateTo);

        return [
            Grid::make([
                Column::make([
                    Box::make('Фильтр', [
                        FormBuilder::make(request()->fullUrl(), FormMethod::GET)
                            ->fields([
                                Grid::make([
                                    Column::make([
                                        Date::make('Дата начала', 'date_from')
                                            ->default($dateFrom?->toDateString()),
                                    ])->columnSpan(6),
                                    Column::make([
                                        Date::make('Дата окончания', 'date_to')
                                            ->default($dateTo?->toDateString()),
                                    ])->columnSpan(6),
                                ]),
                            ])
                            ->submit('Применить', ['class' => 'btn-primary'])
                            ->buttons([
                                ActionButton::make('Сбросить', $this->getUrl())
                                    ->secondary(),
                            ]),
                    ]),
                ])->columnSpan(12),

                Column::make([
                    Box::make([
                        ActionGroup::make([
                            $this->exportButton(),
                        ])->class(['mb-4']),

                        TableBuilder::make()
                            ->fields([
                                Preview::make('Показатель', 'metric'),
                                Preview::make('Значение', 'value'),
                            ])
                            ->items($analyticsData->toArray()),
                    ]),
                ])->columnSpan(12),
            ]),
        ];
    }

    protected function exportButton()
    {
        return ActionButton::make(
            'Выгрузить в Excel',
            '#'
        )
            ->method('export', request()->only(['date_from', 'date_to']))
            ->download()
            ->success()
            ->icon('document-arrow-down');
    }

    #[AsyncMethod]
    public function export(): Response
    {
        $dateFrom = request('date_from') ? Carbon::parse(request('date_from')) : null;
        $dateTo = request('date_to') ? Carbon::parse(request('date_to')) : null;

        $analyticsData = $this->analyticsService->getAnalytics($dateFrom, $dateTo);
        $filename = 'analytics_export_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        $response = new BinaryFileResponse(
            (new FastExcel($analyticsData->toExportArray()))->export($filename)
        );

        // удаление лишних символов в названии файла
        $response->setContentDisposition(
            'attachment',
            $filename,
            str_replace('%', '', $filename)
        );

        return $response;
    }
}
