# simple

Simple PHP
	简单、干净的php框架

author		gavinczhang
email		gavin6487@gmail.com
version		0.3
changed		2014年5月23日 20:14:46
Explanation
		* 允许自定义Route
		* 修改Route的挂载方式
		* 增加config文件route.php	
		* 修改站点的入口为Route::init
		* 暂不支持正则route规则

##目录结构

controller	控制器目录
	welcome.php	默认controller
view		视图目录
model		模型目录

class		核心类库目录
	core.php	核心类
	arr.php		数组操作类
	config.php	config文件读取类
	db.php		mysql操作类
	orm.php		ORM封装
	cookie.php	cookie操作封装
	session.php	session操作封装（*需要ORM、cookie支持）
	controller.php	Controller类
	view.php	视图类
	route.php	路由类
	valid.php	验证参数是否有效
config		配置文件目录
	database.php	数据库配置
	cookie.php	cookie配置
	session.php	session配置
init.php	入口文件

##常用操作

[AJAX输出]
	Core::ajax(array('test' => 123));   =>   json_encode(array('status' => true, 'data' => array('test' => 123)));

[数组操作]
	$row = Arr::get($array, 'test', false);   =>   $row = isset($array['test']) ? $array['test'] : false;

[COOKIE操作]
	增/改	Cookie::set('abc', 'hahaha');
	查	$a = Cookie::get('abc');
	删	Cookie::delete('abc');
	清空	Cookie::clear();

[SESSION操作]
	Session::instance()->get('abc');
	Session::instance()->set('abc', 'edf');
	Session::instance()->delete('abc');
	Session::instance()->clear();

[DB操作]
	$query = DB::instance()->query($sql);
	$row = DB::instance()->fetch($query);
	$value = DB::instance()->check($value);	过滤函数

[ORM操作]
	ORM::factory('cloud.depts')->where(array('rtx' => array('LIKE', '%gavin%')))->find_all();
	ORM::factory('depts')->add(array('rtx' => 'gavinczhang', 'name' => '张超'));
	ORM::factory('depts')->where(array('rtx' => 'gavinczhang', 'name' => '张超'))->delete();
	ORM::factory('depts')->where(array('rtx' => 'gavinczhang', 'name' => '张超'))->save(array('rtx' => 'gavin', 'name' => '张超'));

[VIEW操作]
	View::bind('test', $abc);
	View::bind(array('test' => $abc, 'a' => $b));
	View::display('index');

[Route操作]
	Route::set('list/hello', array('controller' => 'temp', 'action' => 'list'));	表示***.***.***/list/hello的网址，由Controller_Temp类下的action_list方法执行
	Route::set('404', array('controller' => 'error', 'action' => '404'));	表示定义404页面
	//*未来将支持正则表达式
	Route::set('list/<controller>/<action>', array('controller' => 'temp', 'action' => 'list'));	表示所有/list/controller_name/action_name下的网址，均由对应的controller和action执行

##相关错误信息


[Wrong Type 1] 文件(model/class/view/config)不存在
[Wrong Type 2] 初始化参数(cookie)无定义
[Wrong Type 3] URL非法

[Mysql Error] mysql错误号及错误信息
SQL: 错误语句

[ORM Error 1] 表名不得为空
[ORM Error 1] 检索条件不得为空

[HTTP Error 404] controller或action不存在

#-----------------------------------------------#
# 其他说明										#
#-----------------------------------------------#

代码中以 //* 开始的注释字样，表示等待实现功能。
需要尽快实现错误机制

#-----------------------------------------------#
# 更新记录										#
#-----------------------------------------------#

2014年5月11日
· 实现最基础的框架结构规划
· 实现自动挂载
· 实现config文件读取
· 实现cookie操作封装

2014年5月12日
· 实现DB操作封装
· 实现简单的ORM封装

2014年5月13日
· 实现基于数据库的session操作
· 修改del相关函数的调用方式为delete

2014年5月15日
· 实现基础的MVC功能
· 实现默认路由
· 还原为统一入口
· 修改自动加载机制，用以自动挂载model

2014年5月16日
· 修改自动挂载实现方式
· 修复session、orm等语法bug
· DB类增加过滤函数，ORM中过滤函数改为调用DB的过滤函数实现

2014年5月18日
· 删除action必带参数$param
· Controller增加$this->_post与$this->_get方法，过滤get和post参数
* 考虑增加参数自动过滤，每个表单一个固定的md5 key，填写好每个参数的验证方式，在Controller入口处进行统一过滤及返还错误信息

2014年5月19日
· 增加Valid类，判断参数有效

2014年5月21日
· 修复输出错误的bug

2014年5月23日
· 允许自定义Route
· 修改Route的挂载方式
· 增加config文件route.php，用以自定义route规则
· 修改站点的入口Route::run为Route::init 执行Route初始化读入
* 暂不支持复杂的正则格式route规则