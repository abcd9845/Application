<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Exception;

class ExportBasicController extends Controller {

    public function index(){
        $this->display();
    }

    public function export(){
        $goods_list = M()->table('storage')
            ->field("
                 basic_goods.name
                ,goods_type.type_name
                ,'' as tiaoma
                ,storage.count
                ,storage_item.vip as jinhuo
                ,storage_item.vip as xiaoshou
                ,storage_item.vip as pifa
                ,storage_item.vip as huiyuan
                ,0 as jifen
                ,0 as zhekou
                ,10000 as shangxian
                ,0 as xiaxian
                ,'' as gonghuoshang
                ,'' as shangchanriqi
                ,'' as baozhiqi
                ,'' as pinyinma
                ,storage.basic_id as diy1
                ,storage.school_id as diy2
                ,storage_item.show_type as diy3
                ,storage.id as diy4
                ,case storage_item.online when 1 then '禁用' else '启用' end as state
                ,storage_item.description as description
            ")
            ->join('left join basic_goods on basic_goods.id = storage.basic_id')
            ->join('left join storage_item on storage.id = storage_item.storage_id')
            ->join('left join goods_type on goods_type.id = storage_item.show_type')
            ->where('storage.school_id = 7 and storage_item.show_type = -2 and basic_goods.delete_flag = 0')
            ->select();

        foreach($goods_list as $k => $v){
            if($v['diy3'] == -1){
                $goods_list[$k]['type_name'] = '欢乐时光';
            }

            if($v['diy3'] == -2){
                $goods_list[$k]['type_name'] = '现买现提';
            }
        }

        $data = array();
        foreach ($goods_list as $k=>$v){
            $data[$k][name] = $v['name'];
            $data[$k][type_name] = $v['type_name'];
            $data[$k][tiaoma] = $v['tiaoma'];
            $data[$k][count] = $v['count'];
            $data[$k][jinhuo] = $v['jinhuo'];
            $data[$k][xiaoshou] = $v['xiaoshou'];
            $data[$k][pifa] = $v['pifa'];
            $data[$k][huiyuan] = $v['huiyuan'];
            $data[$k][jifen] = $v['jifen'];
            $data[$k][zhekou] = $v['zhekou'];
            $data[$k][shangxian] = $v['shangxian'];
            $data[$k][xiaxian] = $v['xiaxian'];
            $data[$k][gonghuoshang] = $v['gonghuoshang'];
            $data[$k][shangchanriqi] = $v['shangchanriqi'];
            $data[$k][baozhiqi] = $v['baozhiqi'];
            $data[$k][pinyinma] = $v['pinyinma'];
            $data[$k][diy1] = $v['diy1'];
            $data[$k][diy2] = $v['diy2'];
            $data[$k][diy3] = $v['diy3'];
            $data[$k][diy4] = $v['diy4'];
            $data[$k][state] = $v['state'];
            $data[$k][description] = $v['description'];
        }

        foreach ($data as $field=>$v){
            if($field == 'name'){
                $headArr[]='名称（必填）';
            }

            if($field == 'type_name'){
                $headArr[]='分类（必填）';
            }

            if($field == 'tiaoma'){
                $headArr[]='条码';
            }

            if($field == 'count'){
                $headArr[]='库存量（必填）';
            }

            if($field == 'jinhuo'){
                $headArr[]='进货价（必填）';
            }

            if($field == 'xiaoshou'){
                $headArr[]='销售价（必填）';
            }

            if($field == 'pifa'){
                $headArr[]='批发价';
            }
            if($field == 'huiyuan'){
                $headArr[]='会员价';
            }

            if($field == 'jifen'){
                $headArr[]='积分商品';
            }

            if($field == 'zhekou'){
                $headArr[]='会员折扣';
            }

            if($field == 'shangxian'){
                $headArr[]='库存上限';
            }

            if($field == 'xiaxian'){
                $headArr[]='库存下限';
            }

            if($field == 'gonghuoshang'){
                $headArr[]='供货商';
            }

            if($field == 'shangchanriqi'){
                $headArr[]='生产日期';
            }

            if($field == 'baozhiqi'){
                $headArr[]='保质期';
            }

            if($field == 'pinyinma'){
                $headArr[]='拼音码';
            }

            if($field == 'diy1'){
                $headArr[]='自定义1';
            }

            if($field == 'diy2'){
                $headArr[]='自定义2';
            }

            if($field == 'diy3'){
                $headArr[]='自定义3';
            }

            if($field == 'diy4'){
                $headArr[]='自定义4';
            }

            if($field == 'state'){
                $headArr[]='商品状态';
            }

            if($field == 'description'){
                $headArr[]='商品描述';
            }

        }

        $filename="goods_list";

        $this->getExcel($filename,$headArr,$data);
    }

    private  function getExcel($fileName,$headArr,$data){
        //导入PHPExcel类库，因为PHPExcel没有用命名空间，只能inport导入
        import("Org.Util.PHPExcel");
        import("Org.Util.PHPExcel.Writer.Excel5");
        import("Org.Util.PHPExcel.IOFactory.php");

        $date = date("Y_m_d",time());
        $fileName .= "_{$date}.xls";

        //创建PHPExcel对象，注意，不能少了\
        $objPHPExcel = new \PHPExcel();
        $objProps = $objPHPExcel->getProperties();

        //设置表头
        $key = ord("A");
        //print_r($headArr);exit;
        foreach($headArr as $v){
            $colum = chr($key);
            $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);
            $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum.'1', $v);
            $key += 1;
        }

        $column = 2;
        $objActSheet = $objPHPExcel->getActiveSheet();

        //print_r($data);exit;
        foreach($data as $key => $rows){ //行写入
            $span = ord("A");
            foreach($rows as $keyName=>$value){// 列写入
                $j = chr($span);
                $objActSheet->setCellValue($j.$column, $value);
                $span++;
            }
            $column++;
        }

        $fileName = iconv("utf-8", "gb2312", $fileName);
        //重命名表
        //$objPHPExcel->getActiveSheet()->setTitle('test');
        //设置活动单指数到第一个表,所以Excel打开这是第一个表
        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=\"$fileName\"");
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output'); //文件通过浏览器下载
        exit;
    }

}