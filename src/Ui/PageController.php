<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\InventoryCount\Ui;

use ksfraser\FrontAccounting\InventoryCount\Domain\CountCart;
use ksfraser\FrontAccounting\InventoryCount\Exception\BarcodeNotFoundException;
use ksfraser\FrontAccounting\InventoryCount\Exception\HoldingTankNotConfiguredException;
use ksfraser\FrontAccounting\InventoryCount\Fa\FaApiInterface;
use ksfraser\FrontAccounting\InventoryCount\Fa\FrontAccountingApi;
use ksfraser\FrontAccounting\InventoryCount\Repository\CountRepositoryInterface;
use ksfraser\FrontAccounting\InventoryCount\Repository\DbCountRepository;
use ksfraser\FrontAccounting\InventoryCount\Service\ExportService;
use ksfraser\FrontAccounting\InventoryCount\Service\ImportService;
use ksfraser\FrontAccounting\InventoryCount\Service\InventoryCountService;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;

/**
 * Page controller for the Inventory Taking screen.
 *
 * Composition over inheritance: collaborates with injected services instead
 * of extending legacy generic_interface / generic_fa_interface.
 *
 * @UML Note: Class diagram in ProjectDocs/UML.md
 *
 * @since 1.0.0
 */
class PageController
{
    const SESSION_KEY = 'ksf_FA_InventoryCount_cart';

    /** @var CountCart */
    protected $cart;

    /** @var FaApiInterface */
    protected $fa;

    /** @var CountRepositoryInterface */
    protected $repository;

    /** @var InventoryCountService */
    protected $countService;

    /** @var ImportService */
    protected $importService;

    /** @var ExportService */
    protected $exportService;

    /** @var string */
    protected $location;

    /** @var string */
    protected $documentDate;

    /**
     * Constructor - use create() in FA context.
     *
     * @param CountCart                $cart          Cart instance.
     * @param FaApiInterface           $fa            FA API wrapper.
     * @param CountRepositoryInterface $repository    Count persistence.
     * @param InventoryCountService    $countService  Count processor.
     * @param ImportService            $importService CSV import.
     * @param ExportService            $exportService CSV export.
     *
     * @since 1.0.0
     */
    public function __construct(
        CountCart $cart,
        FaApiInterface $fa,
        CountRepositoryInterface $repository,
        InventoryCountService $countService,
        ImportService $importService,
        ExportService $exportService
    ) {
        $this->cart = $cart;
        $this->fa = $fa;
        $this->repository = $repository;
        $this->countService = $countService;
        $this->importService = $importService;
        $this->exportService = $exportService;
        $this->location = isset($_POST['location']) ? (string) $_POST['location'] : '';
        $this->documentDate = isset($_POST['document_date'])
            ? (string) $_POST['document_date']
            : date('Y-m-d');
    }

    /**
     * Wire up the object graph from a FrontAccounting environment.
     *
     * @return self
     *
     * @since 1.0.0
     */
    public static function create(): self
    {
        $db = new FrontAccountingDbAdapter();
        $fa = new FrontAccountingApi();
        return new self(
            new CountCart(),
            $fa,
            new DbCountRepository($db),
            new InventoryCountService($fa, new DbCountRepository($db)),
            new ImportService($fa),
            new ExportService()
        );
    }

    /**
     * Handle POST actions then render the page.
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function run(): void
    {
        page(_('Inventory Taking'));

        $this->handleActions();
        $this->renderHeader();
        $this->renderScanForm();
        $this->renderCart();

        end_page();
    }

    /**
     * Dispatch submitted form actions.
     *
     * @return void
     *
     * @BABOK Related: FR-IC-001-001
     * @since 1.0.0
     */
    protected function handleActions(): void
    {
        if (isset($_POST['AddItem'])) {
            $this->handleScan();
        }
        if (isset($_POST['UpdateItem'])) {
            foreach ((array) ($_POST['qty'] ?? []) as $stockId => $qty) {
                $this->cart->updateQty((string) $stockId, (float) $qty);
            }
        }
        if (isset($_POST['ClearCart'])) {
            $this->cart->clear();
        }
        if (isset($_POST['ProcessCount'])) {
            $this->handleProcess();
        }
    }

    /**
     * Resolve a scanned code and add it to the cart.
     *
     * @return void
     * @throws BarcodeNotFoundException Propagated for UI display.
     *
     * @since 1.0.0
     */
    protected function handleScan(): void
    {
        $code = trim((string) ($_POST['UPC'] ?? ''));
        if ($code === '') {
            display_error(_('Scan a barcode first.'));
            return;
        }
        $candidates = $this->fa->resolveBarcode($code);
        if (count($candidates) === 0) {
            throw new BarcodeNotFoundException("Barcode '{$code}' does not match any stock item.");
        }
        $this->cart->addScan($candidates[0], 1.0, $code);
        display_notification(sprintf(_('%s added to count.'), $candidates[0]));
    }

    /**
     * Process the count through the holding tank.
     *
     * @return void
     *
     * @BABOK Related: FR-IC-001-005
     * @since 1.0.0
     */
    protected function handleProcess(): void
    {
        try {
            $result = $this->countService->process(
                $this->cart,
                $this->location,
                $this->documentDate,
                (string) get_company_pref('inv_count_holdtank'),
                true
            );
            display_notification(
                sprintf(
                    _('Count processed: %d adjustment(s) on transfer %s.'),
                    count($result->getAdjustments()),
                    (string) $result->getTransNo()
                )
            );
            $this->cart->clear();
        } catch (HoldingTankNotConfiguredException $e) {
            display_error($e->getMessage());
        } catch (\Exception $e) {
            display_error($e->getMessage());
        }
    }

    /**
     * Render location/date header.
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function renderHeader(): void
    {
        start_form(true);
        start_outer_table(TABLESTYLE, "width=70%");
        table_section(1);
        locations_list_cells(_('Inventory Location:'), 'location', $this->location, false, false);
        table_section(2, '33%');
        date_row(_('Date:'), 'document_date', $this->documentDate, true);
        end_outer_table(1);
        submit_center_first('RefreshHeader', _('Update'));
        end_form();
    }

    /**
     * Render barcode scan entry box.
     *
     * @return void
     *
     * @BABOK Related: FR-IC-001-001
     * @since 1.0.0
     */
    protected function renderScanForm(): void
    {
        start_form(true);
        start_table(TABLESTYLE, "width=70%");
        label_row(_('Scan Barcode'), text_input('UPC', @$_POST['UPC'], 20, 40));
        hidden('location', $this->location);
        submit_center('AddItem', _('Add Scan'), true, '', 'default');
        end_table();
        end_form();
    }

    /**
     * Render counted lines with edit/delete and process controls.
     *
     * @return void
     *
     * @BABOK Related: FR-IC-001-003
     * @since 1.0.0
     */
    protected function renderCart(): void
    {
        start_form(true);
        div_start('items_table');
        start_table(TABLESTYLE, "width=90%");
        $th = ['#', _('Stock'), _('Barcode'), _('Qty Counted')];
        table_header($th);

        $k = 0;
        foreach ($this->cart->lines() as $line) {
            alt_table_row_color($k);
            qty_cell($line->getStockId());
            label_cell($line->getBarcode());
            qty_cells(null, "qty[{$line->getStockId()}]", $line->getCountedQty(), null, null, 0);
            end_row();
        }

        end_table();
        div_end();

        submit_center_first('UpdateItem', _('Update Counts'));
        submit_center_last('ProcessCount', _('Process Count'), _('Adjust over/short via HOLDING tank'), 'default');
        submit('ClearCart', _('Clear Cart'), true, '', false);
        end_form();
    }
}
