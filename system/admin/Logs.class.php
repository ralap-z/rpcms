<?php
namespace rp\admin;
use rp\View;
use rp\Db;
use rp\Cache;
use rp\Hook;

class Logs extends Base{
	
	private $config;
	public function __construct(){
		parent::__construct();
		$this->config=Cache::read('option');
	}
	
	public function index(){
		$key=input('key') ? input('key') : '';
		$sort=input('sort') ? input('sort') : '';
		$order=input('order') ? input('order') : '';
		$cateId=intval(input('cateId')) ? intval(input('cateId')) : '';
		$authorId=intval(input('authorId')) ? intval(input('authorId')) : '';
		$tagId=intval(input('tagId')) ? intval(input('tagId')) : '';
		$status=input('status') != '' ? intval(input('status')) : 9;
		$page=intval(input('page')) ? intval(input('page')) : 1;
		$limit=10;
		$where=array();
		$search=array();
		if(!empty($key)){
			$where['a.title']=array('like','%'.$key.'%');
			$search[]="key=".$key;
		}
		if(!empty($cateId)){
			$where['a.cateId']=$cateId;
			$search[]="cateId=".$cateId;
		}
		if(!empty($authorId)){
			$where['a.authorId']=$authorId;
			$search[]="authorId=".$authorId;
		}
		if(!empty($tagId)){
			$where['a.tages']=array('find_in_set',$tages);
			$search[]="tagId=".$tagId;
		}
		if($status != 9){
			$where['a.status']=$status;
			$search[]="status=".$status;
		}
		$orderBy=array('a.isTop'=>'desc','a.id'=>'desc');
		if(!empty($sort) && !empty($order)){
			$search[]="sort=".$sort;
			$search[]="order=".$order;
			$orderBy=array(
				'a.'.$sort=>$order,
				'a.id'=>'desc',
			);
		}
		$count=Db::name('logs')->alias('a')->where($where)->field('a.id')->count();
		$pages = ceil($count / $limit);
        if($page >= $pages && $pages > 0){
            $page = $pages;
        }
		$res=Db::name('logs')->alias('a')->join(array(
			array('category as b force index(PRIMARY)','a.cateId=b.id','left'),
			array('user as c force index(PRIMARY)','a.authorId=c.id','left'),
		))->where($where)->field('a.id,a.title,a.comnum,a.upnum,a.views,a.isTop,a.createTime,a.status,b.cate_name,c.nickname')->order($orderBy)->limit(($page-1)*$limit.','.$limit)->select();
		$pageHtml=pageInation($count,$limit,$page,'',join('&',$search));
		View::assign('categoryHtml',me_createCateOption($cateId));
		View::assign('tages',Cache::read('tages'));
		View::assign('list',$res);
		View::assign('s_status',$status);
		View::assign('s_key',$key);
		View::assign('s_sort',$sort);
		View::assign('s_order',$order);
		View::assign('pageHtml',$pageHtml);
		return View::display('/logs_index');
	}
	
	public function add(){
		$logData=array(
			'status'=>2,
			'isTop'=>0,
			'isRemark'=>1,
			'extend'=>false,
		);
		View::assign('logid','');
		View::assign('logData',$logData);
		View::assign('categoryHtml',me_createCateOption());
		View::assign('authorHtml',me_createAuthorOption());
		View::assign('specialHtml',me_createSpecialOption());
		return View::display('/logs_add');
	}
	
	public function update(){
		$id=intval(input('id')) ? intval(input('id')) : 0;
		$logData=Db::name('logs')->where('id='.$id)->find();
		if(empty($logData)){
			redirect(url('logs/add'));
		}
		$tages=Cache::read('tages');
		$tagName=array();
		$tagArr=explode(',',$logData['tages']);
		foreach($tagArr as $v){
			if(isset($tages[$v])){
				$tagName[]=$tages[$v]['tagName'];
			}
		}
		$logData['tagesName']=join(',',$tagName);
		$logData['extend']=json_decode($logData['extend'],true);
		$logData['content']=htmlspecialchars($logData['content']);
		View::assign('logid',$id);
		View::assign('logData',$logData);
		View::assign('categoryHtml',me_createCateOption($logData['cateId']));
		View::assign('authorHtml',me_createAuthorOption($logData['authorId']));
		View::assign('specialHtml',me_createSpecialOption($logData['specialId']));
		return View::display('/logs_add');
	}
	
	public function dopost(){
		$param=input('post.');
		$data=array();
		$data['title']=strip_tags($param['title']);
		$data['content']=clear_html($param['content'],array('script'));
		if(empty($data['title'])){
			return json(array('code'=>-1, 'msg'=>'标题不能为空'));
		}
		if(empty($data['content'])){
			return json(array('code'=>-1, 'msg'=>'正文不能为空'));
		}
		$logid=intval($param['logid']);
		$data['excerpt']=!empty(strip_tags($param['excerpt'])) ? preg_replace('/\s/u','',strip_tags($param['excerpt'])) : getContentByLength($param['content']);
		$data['keywords']=str_replace('，',',',strip_tags($param['keywords']));
		$data['cateId']=intval($param['cateId']);
		$data['authorId']=intval($param['authorId']);
		$data['specialId']=intval($param['specialId']);
		$data['alias']=strip_tags($param['alias']);
		$data['password']=strip_tags($param['password']);
		$data['template']=strip_tags($param['template']);
		$data['createTime']=!empty($param['createTime']) ? date('Y-m-d H:i:s',strtotime($param['createTime'])) : date('Y-m-d H:i:s');
		$data['upateTime']=date('Y-m-d H:i:s');
		$data['isTop']=!empty($param['isTop']) ? intval($param['isTop']) : 0;
		$data['isRemark']=!empty($param['isRemark']) ? intval($param['isRemark']) : 0;
		$data['extend']=$this->extendPost($param);
		$data['status']=intval($param['type']) == 3 ? intval($param['status']) : intval($param['type']);
		$msg='于'.date('H:i:s');
		$this->checkAlias($data['alias']);
		$this->checkTemplate($data['template']);
		if($param['type'] != 2){
			$data['tages']=$this->replaceTages($param['tagesName']);
		}
		if($param['click'] == 'true'){
			$msg=(empty($logid) || ($data['status'] == 0 && $param['status'] != 0)) ? '添加成功' : '修改成功';
		}
		$checkAlias=array();
		if(!empty($data['alias'])){
			$checkAlias=Db::name('logs')->where(array('alias'=>$data['alias']))->field('id')->find();
		}else{
			unset($data['alias']);
		}
		if(!empty($logid)){
			if(!empty($checkAlias) && $checkAlias['id'] != $logid){
				return json(array('code'=>-1, 'msg'=>'别名重复，请更换别名'));
			}
			$res=Db::name('logs')->where('id='.$logid)->update($data);
		}else{
			if(!empty($checkAlias)){
				return json(array('code'=>-1, 'msg'=>'别名重复'));
			}
			$logid=Db::name('logs')->insert($data);
		}
		if(!empty($data['specialId'])){
			Db::name('special')->where('id='.$data['specialId'])->update(array('updateTime'=>date('Y-m-d H:i:s')));
		}
		if($param['type'] != 2){
			$this->updateCache();
		}
		Hook::doHook('admin_logs_save',array($logid));
		return json(array('code'=>200,'msg'=>$msg,'data'=>$logid));
	}
	
	public function oper(){
		$type=input('type') ? input('type') : '';
		$value=intval(input('value')) ? intval(input('value')) : '';
		$ids=(string)input('ids') ? (string)input('ids') : '';
		if(!method_exists($this,'me_'.$type)){
			return json(array('code'=>-1,'msg'=>'无效操作'));
		}
		$idsArr=arrayIdFilter($ids);
		if(empty($idsArr)){
			return json(array('code'=>-1,'msg'=>'提交文章为空'));
		}
		return call_user_func(array($this, 'me_' . $type),$idsArr,$value);
	}
	
	public function upload(){
		$file=isset($_FILES['files']) ? $_FILES['files'] : '';
		$logid=intval(input('logid')) ? intval(input('logid')) : '';
		if(empty($logid)){
			return json(array('code'=>-1,'msg'=>'数据错误，ID为空'));
		}
		$res=uploadFiles($file,$logid);
		if($res['code'] == 200){
			return json(array('code'=>200, 'msg'=>'success'));
		}else{
			return json(array('code'=>-1, 'msg'=>$res['msg']));
		}
	}
	
	/*删除文章*/
	private function me_dele($ids,$value=''){
		$res=Db::name('logs')->where(array('id'=>array('in',$ids)))->dele();//删除文章
		$res2=Db::name('attachment')->where(array('logId'=>array('in',$ids)))->dele();//删除附件
		$res2=Db::name('comment')->where(array('logId'=>array('in',$ids)))->dele();//删除评论
		$this->updateCache();
		Hook::doHook('admin_logs_dele',array($ids));
		return json(array('code'=>200,'msg'=>'删除成功'));
	}
	
	/*移动分类*/
	private function me_move($ids,$value=''){
		if(empty($value)){
			return json(array('code'=>-1,'msg'=>'目标分类不能为空'));
		}
		$cate=Db::name('category')->where('id='.$value)->find();
		if(empty($cate)){
			return json(array('code'=>-1,'msg'=>'目标分类不存在'));
		}
		$res=Db::name('logs')->where(array('id'=>array('in',$ids)))->update(array('cateId'=>$value));
		$this->updateCache();
		return json(array('code'=>200,'msg'=>'移动成功'));
	}
	/*设置置顶*/
	private function me_top($ids,$value=''){
		$value = $value == 1 ? 1 : 0;
		$res=Db::name('logs')->where(array('id'=>array('in',$ids)))->update(array('isTop'=>$value));
		return json(array('code'=>200,'msg'=>($value == 1 ? '置顶' : '取消置顶').'成功'));
	}
	
	/*设置状态*/
	private function me_status($ids,$value=''){
		$value = !empty($value) ? $value : 0;
		$res=Db::name('logs')->where(array('id'=>array('in',$ids)))->update(array('status'=>$value));
		$this->updateCache();
		Hook::doHook('admin_logs_status',array($ids));
		return json(array('code'=>200,'msg'=>'状态设置成功'));
	}
	
	private function replaceTages($tages){
		$tages = str_replace(array(';','，','、'), ',', $tages);
		$tages = RemoveSpaces(strip_tags($tages));
		$tagesArr = explode(',', $tages);
		$tagesArr = array_unique(array_filter($tagesArr));
		if(empty($tagesArr)) return '';
		$tagesArr = array_slice($tagesArr, 0, 10);//最多10个标签
		$data=array();
		$tagesAll=Cache::read('tages');
		$tagesAll=array_column($tagesAll,NULL,'tagName');
		foreach($tagesArr as $value){
			if(isset($tagesAll[$value])){
				$data[]=$tagesAll[$value]['id'];
			}else{
				$data[]=Db::name('tages')->insert(array('tagName'=>$value));
			}
		}
		return join(',',$data);
	}
	
	private function updateCache(){
		if(!isset($this->config['isPostUpCache']) || $this->config['isPostUpCache'] != 1) return;
		Cache::update('tages');
		Cache::update('category');
		Cache::update('special');
		Cache::update('total');
		Cache::update('logRecord');
	}
}
