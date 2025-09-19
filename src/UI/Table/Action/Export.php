<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

use ILIAS\UI\Implementation\Component\Table\Data;
use ILIAS\UI\Implementation\Component\Table\Column\Column;
use ILIAS\Plugin\LongEssayAssessment\UI\Item\FormGroup;
use ILIAS\Plugin\LongEssayAssessment\UI\Item\FormItem;
use ILIAS\Filesystem\Stream\Stream;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\UI\Component\Table\DataRow;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ILIAS\UI\Component\Component;
use ILIAS\Data\Range;

class Export extends Action
{
    public function __construct(string $name, string $button_label, protected string $filename, protected ExportType $export_type)
    {
        parent::__construct($name, $button_label, Type::Multi);
    }

    public function enabled(Item $item): bool
    {
        return true;
    }

    public function export(Component|Data|FormGroup $table, ?array $selected_columns = null, ?array $selected_rows = null, bool $all_rows_selected = false): Stream
    {
        $header = [];
        $rows = [];

        switch (true) {
            case $table instanceof Data:
                if($selected_columns !== null) {
                    $columns = array_filter(
                        $table->getColumns(),
                        fn($key) => in_array($key, $selected_columns),
                        ARRAY_FILTER_USE_KEY
                    );

                } else {
                    $columns = $table->getVisibleColumns();
                }
                $header = array_map(fn (Column $c) => $c->getTitle(), $columns);

                $rows_generator = $table->getDataRetrieval()->getRows(
                    $table->getRowBuilder()->withVisibleColumns($columns),
                    array_keys($columns),
                    empty($selected_rows) || $all_rows_selected ? new Range(0, PHP_INT_MAX): $table->getRange(),
                    $table->getOrder(),
                    $table->getFilter(),
                    $table->getAdditionalParameters()
                );

                $rows = [];
                /** @var DataRow $row */
                foreach ($rows_generator as $row) {
                    $r = [];
                    if(empty($selected_rows) || in_array((int)$row->getId(), $selected_rows)) {
                        foreach ($columns as $key => $column) {
                            $r[$key] = $row->getCellContent($key);
                        }
                        $rows[] = $r;
                    }
                }
                break;
            case $table instanceof FormGroup:
                $rows = array_map(fn (FormItem $i) => $this->itemToRow($i), $table->getItems());
                if($selected_columns !== null) {
                    $header = array_intersect_key($this->rowsToHeader($rows), array_flip($selected_columns));
                } else {
                    $header = $this->rowsToHeader($rows);
                }
                break;
            default:
                throw new \ValueError("Wrong Component Type. Only FormGroup and DataTable allowed.");
        }

        return match($this->getExportType()) {
            ExportType::CSV => $this->buildCSV($header, $rows),
            ExportType::EXCEL => $this->buildExcel($header, $rows)
        };
    }

    private function buildExcel(array $header, array $rows)
    {
        $excel = new \ilExcel();
        $columns = [];
        $c = 0;
        $r = 1;

        $excel->addSheet($this->name, true);


        foreach ($header as $key => $title) {
            $columns[] = $key;
            $excel->setCell($r, $c++, $title);
        }

        foreach ($rows as $row) {
            $c = 0;
            $r++;
            foreach ($columns as $column) {
                $excel->setCell($r, $c++, $row[$column]??"");
            }
        }
        $tmp = $excel->writeToTmpFile();
        return Streams::ofResource(fopen($tmp, "a+"));
    }

    private function buildCSV(array $header, array $rows)
    {
        $csv = new \ilCSVWriter();
        $columns = [];

        foreach ($header as $key => $title) {
            $columns[] = $key;
            $csv->addColumn($title);
        }

        foreach ($rows as $row) {
            $csv->addRow();
            foreach ($columns as $column) {
                $csv->addColumn($row[$column]??"");
            }
        }

        return Streams::ofString($csv->getCSVString());
    }

    private function itemToRow(FormItem $item): array
    {
        $values = [
            'title' => $item->getTitle(),
            'description' => $item->getDescription(),
        ];

        foreach ($item->getProperties() as $key => $value) {
            if (!is_string($value) && !is_numeric($value)) {
                continue;
            }
            $values[$key] = (string) $value;
        }
        return $values;
    }

    private function rowsToHeader(array $rows): array
    {
        $header = [];

        foreach ($rows as $row) {
            $new = [];

            foreach (array_diff($header, array_keys($row)) as $title) {
                $new[$title] = $title;
            }

            $header += $new;
        }

        return $header;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getExtension(): string
    {
        return $this->export_type->extension();
    }

    public function getMimeType(): string
    {
        return $this->export_type->mimetype();
    }

    public function getExportType(): ExportType
    {
        return $this->export_type;
    }
}
