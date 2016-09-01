<?php
namespace Admin\Controller;
use Think\Controller;

class BasicGoodsController extends Controller {

    public function index(){
        $this->assign('basic_type',D('BasicGoodsType')->getBasicType());

        $filters = array(
            basic_name=>'',
            basic_type=>'',
            delete_flag=>0
        );
        merge_filter($filters);
        $this->assign('filters', $filters);


        $where = array();
        add_where('goods.name',$filters['basic_name'],$where,'like');
        add_where('goods.goods_basic_type',$filters['basic_type'],$where);
        add_where('goods.delete_flag',$filters['delete_flag'],$where);

        $m = M();
        $m->table('basic_goods as goods')->field('goods.id,goods.name,basic_goods_type.basic_type')
        ->join('left join basic_goods_type on goods.goods_basic_type = basic_goods_type.id')
        ->where($where)
        ->order('goods.oper_time desc');
        $m1 = clone($m);
        $m2 = clone($m);

        $count = $m1->count();
        $page = new \Think\Page($count,PAGECOUNT);
        add_param($page->parameter,$filters);
        $show = $page->show();
        $arr = $m2->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('array',$arr);
        $this->assign('show',$show);
        $this->display();
    }

    public function toAdd(){
        $this->assign('basic_type',D('BasicGoodsType')->getBasicType());
        $this->display();
    }

    public function add(){
        $data['name'] = $_REQUEST['name'];
        $data['goods_no'] = $_REQUEST['goods_no'];
        $data['goods_basic_type'] = $_REQUEST['goods_basic_type'];
        $data['oper_user'] = $_SESSION['current_user']['id'];
        $data['oper_time'] = date('Y-m-d H:i:s',time());
        $data['title_pic'] = './'.$this::getPublicFilePath($_REQUEST['title_pic']);
        $data['description'] = $_REQUEST['description'];

        $m = M('basic_goods');
        $result1 = $m->data($data)->add();

        $m_img = M('img_mapping');
        $img_key = array();
        $img_result = $m_img->where('user = '.$_SESSION['current_user']['id'])->select();
        for($i = 0;$i<count($img_result);$i++){
            if($data['title_pic'] == $img_result[$i]['img']){

            }else{
                unlink($img_result[$i]['img']);
            }
            array_push($img_key,$img_result[$i]['id']);
        }

        $nm_img = clone(M('img_mapping'));
        $nm_img->delete(implode(',', $img_key));

        $this->success('添加成功','index');
    }

    public function toEdit(){
        $id = I('request.id');
        $this->assign('id',$id);
        $m = M('basic_goods')->where(array(id=>$id))->find();

        $this->assign('obj',$m);

        $this->assign('basic_type',D('BasicGoodsType')->getBasicType());

        $this->display();
    }

    public function toView(){
        $id = $_REQUEST['id'];
        $this->assign('id',$id);
        $m = M();
        $m->table('basic_goods as goods')->field('basic_goods_type.basic_type,goods.id,goods.title_pic,goods.name,goods.description')
            ->join('left join basic_goods_type on goods.goods_basic_type = basic_goods_type.id')
            ->where("goods.id = ".$id);
        $array = $m->find();
        $this->assign('t',$array);
        $this->display();
    }

    public function edit(){
        $data['id'] = $_REQUEST['id'];
        $data['name'] = $_REQUEST['name'];
        $data['goods_no'] = $_REQUEST['goods_no'];
        $data['goods_basic_type'] = $_REQUEST['goods_basic_type'];
        $data['oper_user'] = $_SESSION['current_user']['id'];
        $data['oper_time'] = date('Y-m-d H:i:s',time());
        $data['description'] = $_REQUEST['description'];

        if($_REQUEST['title_pic'] != '')
            $data['title_pic'] = './'.$this::getPublicFilePath($_REQUEST['title_pic']);


        $m = M('basic_goods');
        $result1 = $m->data($data)->save();

        if($_REQUEST['title_pic']!=''){
            $m_img = M('img_mapping');
            $img_key = array();
            $img_result = $m_img->where('user = '.$_SESSION['current_user']['id'])->select();
            for($i = 0;$i<count($img_result);$i++){
                if($data['title_pic'] == $img_result[$i]['img']){

                }else{
                    unlink($img_result[$i]['img']);
                }
                array_push($img_key,$img_result[$i]['id']);
            }

            $nm_img = clone(M('img_mapping'));
            $nm_img->delete(implode(',', $img_key));
        }

        if($result1>=0){
            if($_REQUEST['title_pic'] != ''){
                unlink($_REQUEST['old_title_pic']);
            }

            $this->success('修改成功','index');
        }else{
            $m->rollback();
            $this->error('修改失败','index');
        }

    }

    public function del(){
        if($_REQUEST['offline'] === 'true'){
            $storageList = M('storage')->where('basic_id in ('.$_REQUEST['id'].')')->select();
            $ids = array();
            foreach($storageList as $key => $val){
                array_push($ids,$val['id']);
            }
            M('storage_item')->where('storage_id in ('.join($ids,',').')')->save(array(online=>1));
        }
        $m = M('basic_goods');
        $data['delete_flag'] = 1;
        $m->where('id in ('.$_REQUEST['id'].')')->save($data);

        $this->success('删除成功','index');
    }

    public function ImgSaveToFile(){
        $config = array(
            'maxSize' => 200 * 1024 * 1024, // 单位是b
            'rootPath' => './Public/images/product/',
            'autoSub'=> false,
            'exts' => array(
                'jpg',
                'gif',
                'png',
                'jpeg'
            )
        );
        $upload = new \Think\Upload($config);
        $info = $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $response = array(
                "status" => 'error',
                "message" => 'something went wrong',
            );
        }else{// 上传成功 获取上传文件信息
            $image = new \Think\Image();
            $image->open('./Public/images/product/'.$info['img']['savepath'].$info['img']['savename']);
            $response = array(
                "status" => 'success',
                "url" => __ROOT__.'/Public/images/product/'.$info['img']['savepath'].$info['img']['savename'],
                "width" => $image->width(),
                "height" => $image->height()
            );
        }
        print json_encode($response);

    }

    public function ImgCropToFile(){
        /*
*	!!! THIS IS JUST AN EXAMPLE !!!, PLEASE USE ImageMagick or some other quality image processing libraries
*/
        $imgUrl = $_POST['imgUrl'];
// original sizes
        $imgInitW = $_POST['imgInitW'];
        $imgInitH = $_POST['imgInitH'];
// resized sizes
        $imgW = $_POST['imgW'];
        $imgH = $_POST['imgH'];
// offsets
        $imgY1 = $_POST['imgY1'];
        $imgX1 = $_POST['imgX1'];
// crop box
        $cropW = $_POST['cropW'];
        $cropH = $_POST['cropH'];
// rotation angle
        $angle = $_POST['rotation'];

        $jpeg_quality = 100;

        $what = new \Think\Image();
        $name = './'.$this::getPath($imgUrl).$this::getFileName($imgUrl);
        $c_name = './'.$this::getPath($imgUrl).'c_'.$this::getFileName($imgUrl);
        $p_name = './'.$this::getPath($imgUrl).'p_'.$this::getFileName($imgUrl);
        $what ->open($name);
        $what ->thumb($imgW, $imgH)->save($c_name);
        $what ->open($c_name);
        $what ->crop(($cropW),($cropH),$imgX1,$imgY1)->save($p_name);
        unlink($c_name);
        unlink($name);

        $m = M('img_mapping');
        $data = array();
        $data['user'] = $_SESSION['current_user']['id'];
        $data['img'] = './'.$this::getPath($p_name).$this::getFileName($p_name);
        $m->data($data)->add();

        $response = Array(
            "status" => 'success',
            "url" => __ROOT__.'/'.$this::getPath($p_name).$this::getFileName($p_name)
        );
        print json_encode($response);
    }

    function getPath($name){
        return substr($name,stripos($name, 'Public'),strrpos($name,'/')-stripos($name, 'Public')+1);
    }

    function getFileName($name){
        return substr($name,strrpos($name,'/')+1);
    }

    function getPublicFilePath($name){
        return substr($name,stripos($name, 'Public'));
    }

}