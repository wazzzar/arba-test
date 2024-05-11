<?php
require_once DIR_SYSTEM . "library/PHPExcel.php";
require_once DIR_SYSTEM . 'library/PHPExcel/Writer/Excel2007.php';
require_once DIR_SYSTEM . 'library/PHPExcel/IOFactory.php';

class ControllerToolPaexport extends Controller
{

    public function index()
    {
        $this->load->model('catalog/category');
        $this->load->model('catalog/attribute');

        $data['tab_export'] = 'Экспорт атрибутов';
        $data['tab_import'] = 'Импорт атрибутов';

        $data['text_select_all'] = 'Выбрать все';
        $data['text_unselect_all'] = 'Снять все';
        $data['text_select_cats'] = 'Выбирете категории';
        $data['text_select_attrs'] = 'Выбирете атрибуты';
        $data['text_backup'] = 'Резервное копирование';

        $data['title_export'] = 'Экспорт';
        $data['title_import'] = 'Импорт';
        $data['title_backup_do'] = 'Сделать бекап';
        $data['title_backup_done'] = 'Бекап уже есть';
        $data['title_restore_do'] = 'Восстановить из резерва';
        $data['title_restore_done'] = 'Уже восстановленно';

        $data['heading_title'] = 'Экспорт/импорт атрибутов';

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['breadcrumbs'][] = array(
            'text' => $data['heading_title'],
            'href' => $this->url->link('tool/paexport', 'token=' . $this->session->data['token'], 'SSL')
        );

        $data['categories'] = $this->model_catalog_category->getCategories(['sort' => 'name', 'order' => 'ASC']);

        $data['attributes'] = $this->model_catalog_attribute->getAttributes(['sort' => 'ad.name', 'order' => 'ASC']);

        $data['export'] = $this->url->link('tool/paexport/export', 'token=' . $this->session->data['token'], 'SSL');

        //if (!$this->checkBackup()) {
        $data['backup'] = $this->url->link('tool/paexport/backup', 'token=' . $this->session->data['token'], 'SSL');
        //}

        $data['import'] = '';
        $data['restore'] = '';
        if ($this->checkBackup()) {
            $data['import'] = $this->url->link('tool/paexport/import', 'token=' . $this->session->data['token'], 'SSL');
            $data['restore'] = $this->url->link('tool/paexport/restore', 'token=' . $this->session->data['token'], 'SSL');
        }

        $data['error'] = '';
        $data['success'] = '';
        if (isset($this->request->get['export_result'])) {
            if ((int)$this->request->get['export_result']) {
                $data['success'] = 'Экспорт выполнен <a href="/tmp/attributes.xlsx" download="">Скачать файл</a>';
            } else {
                $data['error'] = 'Экспорт не удался';
            }
        }

        if (isset($this->request->get['import_result'])) {
            if ((int)$this->request->get['import_result']) {
                $data['success'] = 'Импорт выполнен';
            } else {
                $data['error'] = 'Импорт не удался';
            }
        }

        if (isset($this->request->get['backup_result'])) {
            if ((int)$this->request->get['backup_result']) {
                $data['success'] = 'Резервное копирование выполненно';
            } else {
                $data['error'] = 'Резервное копирование не выполненно';
            }
        }

        if (isset($this->request->get['restore_result'])) {
            if ((int)$this->request->get['restore_result']) {
                $data['success'] = 'Восстановленно из резервной копии';
            } else {
                $data['error'] = 'Не удалось восстановить из резервной копии';
            }
        }

        if (isset($this->request->get['tab'])) {
            $data['tab'] = $this->request->get['tab'];
        } else {
            $data['tab'] = 'export';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('tool/paexport.tpl', $data));
    }

    /**
     * @throws PHPExcel_Exception
     */
    public function export()
    {
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('catalog/attribute');

        $xls = new PHPExcel();
        $xls->getProperties()->setTitle("Тестовое задание");
        $xls->getProperties()->setSubject("АРБА Дистрибьюшн");
        $xls->getProperties()->setCreator("Исаков Дмитрий");
        $xls->getProperties()->setCreated(date('d.m.Y'));

        $xls->setActiveSheetIndex(0);
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('Атрибуты');
        $sheet->freezePane("D2"); // фризим первую строку и первые 3 колонки

        $attributes = $this->request->post['attribute']; // список атрибутов
        $col_string = PHPExcel_Cell::stringFromColumnIndex(count($attributes) + 2); // адрес последней колонки
        $sheet->getStyle("A1:$col_string" . '1')->applyFromArray( // красный бордер первой строки
            array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('rgb' => 'FF0000')
                    )
                )
            )
        );

        $sheet->setCellValue("A1", "ID");
        $sheet->setCellValue("B1", "Name");
        $sheet->setCellValue("C1", "SKU");

        // колонки атрибутов
        foreach ($attributes as $i => $attribute_id) {
            $attributes[$i] = $this->model_catalog_attribute->getAttribute($attribute_id);
            $sheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($i + 3) . '1', htmlspecialchars_decode($attributes[$i]['name']));
        }

        $config_language_id = (int)$this->config->get('config_language_id');
        $start_row = 2;
        // берем категории
        $categories = $this->request->post['categories']; // список категории
        foreach ($categories as $index => $category_id) {

            // в обходе категорий берем товары
            $category_products = $this->model_catalog_product->getProductsByCategoryId($category_id);
            foreach ($category_products as $category_product) {
                // пишем в таблицу
                $sheet->setCellValue("A$start_row", $category_product["product_id"]);
                $sheet->setCellValue("B$start_row", $category_product["name"]);
                $sheet->setCellValue("C$start_row", $category_product["sku"]);

                // в обходе товаров берем атрибуты
                $category_product_attrs_ids = []; // массив id атрибутов продукта
                $category_product_attrs = $this->model_catalog_product->getProductAttributes($category_product['product_id']);
                foreach ($category_product_attrs as $indx => $category_product_attr) {
                    $category_product_attrs_ids[$indx] = $category_product_attr['attribute_id'];
                }

                foreach ($attributes as $idx => $attribute) {
                    $attr_col = PHPExcel_Cell::stringFromColumnIndex($idx + 3) . $start_row;
                    // если у продукта есть атрибут
                    if (($arr_idx = array_search($attribute['attribute_id'], $category_product_attrs_ids)) != false) {
                        // по найденному индексу берем значение атрибута
                        $text = $category_product_attrs[$arr_idx]['product_attribute_description'][$config_language_id]['text'];

                        $sheet->setCellValueExplicit($attr_col, htmlspecialchars_decode($text), PHPExcel_Cell_DataType::TYPE_STRING);
                    } else {
                        $sheet->setCellValue($attr_col, "");
                    }
                }

                $start_row++;
            }
        }

        $file = DIR_SYSTEM . '../tmp/attributes.xlsx';
        if (file_exists($file)) unlink($file);

        $obj_writer = new PHPExcel_Writer_Excel2007($xls);
        $obj_writer->save($file);
        $res = file_exists($file) && filesize($file) > 0;

        // удаляем бакап
        $this->delBackup();
        $red = $this->url->link('tool/paexport', "export_result=$res&tab=export&token=" . $this->session->data['token'], 'SSL');
        $this->response->redirect($red, 301);
    }

    public function import()
    {
        $this->load->model('catalog/attribute');
        $this->load->model('catalog/product');

        $xls = PHPExcel_IOFactory::load($this->request->files['import']['tmp_name']);
        $xls->setActiveSheetIndex(0);
        $sheet = $xls->getActiveSheet();

        $time_start = time();
        $log = 'начало импорта ' . date('Y.m.d H:i:s', $time_start) . "\n";
        // список атрибутов из excel
        $attrs_list = [];
        $attrs_offset = 3;
        while (
            $attr_name = $sheet->getCell(
                ($col_name = PHPExcel_Cell::stringFromColumnIndex($attrs_offset)) . '1'
            )->getValue()
        ) {
            $attrs_list[$col_name] = $attr_name;
            $attrs_offset++;
        }
        $attrs_count = count($attrs_list);

        // обработка атрибутов
        $attributes_arr = [];
        $not_found = [];
        $skipped = [];
        foreach ($attrs_list as $col_name => $attr_name) {
            // поиск атрибута (могут быть похожие или дубли из-за LIKE)
            $attributes = $this->model_catalog_attribute->getAttributes(['filter_name' => htmlspecialchars($attr_name)]); // & -> &amp; " -> &quot;
            // атрибута нет в системе
            if (count($attributes) == 0) {
                $log .= "не обнаружено атрибута '$attr_name' - игнор\n";
                unset($attrs_list[$col_name]);
                $not_found[] = $attr_name;
                continue;
            }
            // атрибут похож или дублируется в системе
            if (count($attributes) > 1) {
                $names = [];
                foreach ($attributes as $attribute) {
                    $names[] = $attribute['name'];
                }
                // $names[] = 'Размер'; тест дубля
                // $names[] = 'Тип'; тест дубля
                $count = array_count_values($names);
                if ($count[$attr_name] > 1) {
                    $log .= "обнаружены одинаковые атрибуты '$attr_name' - игнор\n";
                    $skipped[] = $attr_name;
                    continue;
                }
            }
            $attributes_arr[$attr_name] = $attributes[0];
        }
        if ($attrs_count == count($attrs_list)) {
            $log .= "все атрибуты ($attrs_count) найдены\n";
        }
        if (count($skipped) || count($not_found)){
            $attrs = implode(', ', array_merge($skipped, $not_found));
            $log .= "некоторые атрибуты ($attrs) не были найдены или имееют дубли, они не учитываются в обработке\n";
        }

        // обработка товаров
        $config_language_id = (int)$this->config->get('config_language_id');
        $start_row = 2;
        $count = 0;
        while (
            $product_id = (int)$sheet->getCell("A" . $start_row)->getValue()
        ) {
            $data['product_attribute'] = [];
            foreach ($attrs_list as $col_name => $attr_name) {
                if (in_array($attr_name, $skipped)){
                    $attributes = $this->model_catalog_attribute->getAttributes(['filter_name' => htmlspecialchars($attr_name)]);
                    $product_attrs = $this->model_catalog_product->getProductAttributes($product_id);
                    foreach ($product_attrs as $product_attr){
                        if ( $product_attr['attribute_id'] = $attributes[0]['attribute_id'] ){
                            $data['product_attribute'][] = $product_attr;
                        }
                    }
                }

                $attr_val = $sheet->getCell($col_name . $start_row)->getValue();
                if ($attr_val != NULL) {
                    $data['product_attribute'][] = [
                        'attribute_id' => $attributes_arr[$attr_name]['attribute_id'],
                        'product_attribute_description' => [
                            $config_language_id => ['text' => $attr_val]
                        ]
                    ];
                }
            }
            $this->updateProduct($product_id, $data);

            $start_row++;
            $count++;
        }

        $log .= "было обработанно $count товаров\n";
        $log .= "время обработки: " . (time() - $time_start) . " сек.";

        file_put_contents(DIR_SYSTEM . '../tmp/import.log', $log);

        $res = 1;
        $red = $this->url->link('tool/paexport', "import_result=$res&tab=import&token=" . $this->session->data['token']);
        $this->response->redirect($red, 301);
    }

    public function backup()
    {
        $this->delBackup();
        $this->db->query("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");

        $this->db->query("CREATE TABLE `" . DB_PREFIX . "product_bexp` LIKE `" . DB_PREFIX . "product`");
        $this->db->query("INSERT INTO  `" . DB_PREFIX . "product_bexp` SELECT * FROM `" . DB_PREFIX . "product`");

        $this->db->query("CREATE TABLE `" . DB_PREFIX . "product_attribute_bexp` LIKE `" . DB_PREFIX . "product_attribute`");
        $this->db->query("INSERT INTO  `" . DB_PREFIX . "product_attribute_bexp` SELECT * FROM `" . DB_PREFIX . "product_attribute`");

        $res = $this->checkBackup() ? 1 : 0;
        $red = $this->url->link('tool/paexport', "backup_result=$res&tab=import&token=" . $this->session->data['token'], 'SSL');
        $this->response->redirect($red, 301);
    }

    public function restore()
    {
        if ( $this->checkBackup() ){
            $this->db->query("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");

            $this->db->query("DELETE FROM `" . DB_PREFIX . "product`");
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product` SELECT * FROM `" . DB_PREFIX . "product_bexp`");

            $this->db->query("DELETE FROM `" . DB_PREFIX . "product_attribute`");
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_attribute` SELECT * FROM `" . DB_PREFIX . "product_attribute_bexp`");
            $this->delBackup();
        }

        $res = $this->checkBackup() ? 0 : 1;
        $red = $this->url->link('tool/paexport', "restore_result=$res&tab=import&token=" . $this->session->data['token'], 'SSL');
        $this->response->redirect($red, 301);
    }

    private function checkBackup()
    {
        $product                = $this->db->query("SELECT COUNT(*) as c1 FROM `" . DB_PREFIX . "product`");
        $product_bexp           = $this->db->query("SELECT COUNT(*) as c1 FROM `" . DB_PREFIX . "product_bexp`");
        $product_attribute      = $this->db->query("SELECT COUNT(*) as c2 FROM `" . DB_PREFIX . "product_attribute`");
        $product_attribute_bexp = $this->db->query("SELECT COUNT(*) as c2 FROM `" . DB_PREFIX . "product_attribute_bexp`");

        return
            (int)$product->row['c1']           == (int)$product_bexp->row['c1'] &&
            (int)$product_attribute->row['c2'] == (int)$product_attribute_bexp->row['c2'];
    }

    private function delBackup()
    {
        $sql = "DROP TABLE IF EXISTS `" . DB_PREFIX . "product_bexp`";
        $product = $this->db->query($sql);
        $sql = "DROP TABLE IF EXISTS `" . DB_PREFIX . "product_attribute_bexp`";
        $product_attribute = $this->db->query($sql);
        return $this->checkBackup();
    }

    private function updateProduct($product_id, $data){
        if (count($data['product_attribute'])) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }
    }
}
