# simple

	Simple PHP
		简单、干净的php框架

	author		simple
	email		gavin6487@gmail.com
	version		0.6
	changed		2017年07月15日
	Explanation
		* 重新定义目录结构
		* 框架独立目录
		* CLI模式支持
		* 环境变量对应config子目录功能
		* model模型修改

## 感谢
	感谢kohana/swoole/thinkphp框架,参考借鉴了许多代码及思路

## namespace规则

	按目录区分,目录及文件名全部小写
	namespace首字母大写
	目录						namespace
	app						App
		app\controllers		App\Controllers
		app\models			App\Models
	simple					Simple

## 目录结构

	app							项目目录 此目录可移动至任意目录作为独立app项目,需要修改app/index.php
		business					业务逻辑层,讲复杂的业务逻辑在此封装,controller总调用此层进行逻辑处理,此层调用model或直接使用DB build进行数据变更
		classes						项目特殊定义的class文件目录
		configs						配置文件目录
			dev							环境变量设置为dev时的配置文件陌路
				database.php				dev模式下数据库配置文件
			database.php				默认环境数据库配置文件
			cookie.php					cookie配置文件
			redis.php					redis配置文件
			session.php					session配置文件
		controllers					controller
		htdocs						静态文件目录及入口文件目录
			index.php					入口文件
		models						模型文件 一个表对应一个model
		views						模板文件
		router.php					项目Route自定义

	simple						框架
		build						sql build相关目录
			delete.php					delete相关SQL
			insert.php					insert相关SQL
			select.php					select相关SQL
			update.php					update相关sql
		controller					controller相关目录
			ajax.php					只用ajax的controller基类
			cli.php						脚本运行的基类
			template.php				使用模板的controller基类
		session						session相关目录
			mysql.php					使用mysql存储session
			redis.php					使用redis存储session
		arr.php						常用数组操作
		build.php					构造SQL基类 build目录下所有类均继承于此类
		config.php					配置文件读取
		controller.php				controller基类
		cookie.php					cookie封装
		curl.php					curl封装
		db.php						数据库类
		error.php					异常类	TODO 分DB异常和默认异常
		http.php					response
		orm.php						对象关系映射	TODO 完善
		redis.php					redis封装
		route.php					路由
		session.php					session封装
		simple.php					框架核心文件
		view.php					模板

	index.php					框架入口文件

## nginx配置

	server {
			listen 80;
			server_name simple.com;

			root /data/www/vhost/simple/app/htdocs/;
			index index.php index.html;
			access_log /data/logs/nginx/simple.log main;

			location / {
				if (!-e $request_filename) {
						rewrite ^/(.*)$ /index.php;
				}
			}

			location ~ \.php$ {
				client_max_body_size 128m;

				proxy_set_header X-Real-IP $remote_addr;
				fastcgi_pass	127.0.0.1:9000;
				fastcgi_index	index.php;
				fastcgi_param	SCRIPT_FILENAME  $document_root$fastcgi_script_name;
				include			fastcgi_params;
			}
	}

## HTTP模式

	#以下默认的welcome和index可以通过route进行配置
	url								file->function
	http://your_host/				app/controllers/welcome.php->action_index()
	http://your_host/abc			app/controllers/abc.php->action_index()
	http://your_host/abc/def		app/controllers/abc.php->action_def()
	http://your_host/abc/def/123	app/controllers/abc.php->action_def(123)

## CLI模式

	#需要cli执行的脚本,请继承于\Simple\Controller\Cli 或 在脚本头部判断php_sapi_name
	#cd app/htdocs/
	shell							file->function
	#php index.php					app/controllers/welcome.php->action_index()
	#php index.php abc				app/controllers/abc.php->action_index()
	#php index.php abc/def			app/controllers/abc.php->action_def()
	#php index.php abc/def/123		app/controllers/abc.php->action_def(123)

## 全局变量说明

	DEBUG		bool	调试模式开关,为真时会输出相关错误信息
	CFG_PATH	string	config子目录环境变量,设置后会优先从configs/CFG_PATH/中读取config文件
	APP_PATH	string	app所在目录
	SYS_PATH	string	simple框架核心文件所在目录
	PUB_PATH	string	框架入口文件所在目录
	CLI_MODE	bool	是否是CLI模式执行

## 初始化

	[1. 框架独立]
		将simple目录及index.php[框架入口文件]存放至某独立目录中
		eg:	/data/public/framework

	[2. 项目目录独立]
		将app目录完整move/copy至项目所在目录
		eg: /data/webroot/test/

	[3. 初始化]
		修改app/htdocs/index.php
		debug		调试模式开关
		CFG_PATH	自定义环境变量,用于config子目录,不需要请设置为空字符串
			eg:	define('CFG_PATH', 'dev');
			此时框架会默认从configs/dev/目录下优先读取配置文件,不存在则从configs目录下读取
		修改框架入口文件的真实路径
			eg: require_once '/data/public/framework/index.php';

	[4. 设置路由规则]
		修改app/router.php自定义路由规则
		不需要自定义请清空文件或删除

	[5. DB初始化]
		修改configs/database.php 如设置了CFG_PATH,则需要修改configs/CFG_PATH/database.php
		eg:
			return [
				'master'	=> [
					'hostname'	=> 'localhost',
					'username'	=> 'root',
					'database'	=> 'simple',
					'password'	=> '',
					'pconnect'	=> FALSE,
					'charset'	=> 'utf8mb4',
				],
				'slave'		=> [
					'hostname'	=> 'localhost',
					'username'	=> 'root',
					'database'	=> 'simple_test',
					'password'	=> '',
					'pconnect'	=> FALSE,
					'charset'	=> 'utf8mb4',
				],
			];
		此时
			DB::instance()->query($sql)
			ORM::facotry(table_name)->add($data)
		等相关DB操作,会默认读取此配置文件中的master节点的库
		如需要访问slave节点的库,则
			DB::instance('slave')->query($sql)
			ORM::facotry('slave.table_name')->add($data)
			Model类需要设置$_table = 'slave.table_name'

	[6. Redis初始化]
		修改configs/redis.php 如设置了CFG_PATH,则需要修改configs/CFG_PATH/redis.php
		eg:
			return [
				'ip'	=> '127.0.0.1',
				'port'	=> '6379',
			];

	[7. Session初始化]
		修改configs/session.php 如设置了CFG_PATH,则需要修改configs/CFG_PATH/session.php
		eg:
			return [
				'mode'		=> 'redis',			//mysql or redis
				'mysql'		=> [
					'name'		=> '_session_id',
					'table'		=> 'sessions',
					'gc'		=> 500,
					'columns'	=> [
						'session_id'	=> 'session_id',
						'last_active'	=> 'last_active',
						'contents'		=> 'contents',
					],
					'expire'	=> 2592000,		//30 day
				],
				'redis'		=> [
					'name'		=> '_session_id',
					'prefix'	=> 'SESSION_',
					'expire'	=> 2592000,		//30 day
				],
			];
		session支持两种模式存储mysql/redis
		mode值用来定义当前选择哪种模式存储
		mysql节点用来配置mysql存储时的信息
			name		cookie名
			table		表名
			gc			触发回收概率
			columns		字段名
			expire		session过期时间
		redis节点用来配置redis存储时的信息
			name		cookie名
			prefix		redis存储中的前缀
			expire		过期时间
		session具体使用方式请参看[常用操作/Session]部分

	[8. model初始化]
		一个model对应一张表
		eg: users表,主键是uid
		则创建models/users.php 名字可自定义,需要配置$_table(表名)和$_pk(主键名)的值
		<?php
		namespace App\Models;
		use Simple\Model;
		class Users extends Model
		{
			public $_table = 'users';
			public $_pk = 'uid';
		}

		//使用
		//获取一个uid=3的object
		$model = Users::factory(3);
		//修改该model的值,并使之同步到数据库 接上述
		$model->name='abc';
		$model->save();

		//获取一个空的object
		$model = Users::factory();
		//新增一条数据 接上述
		$model->uid=3;
		$model->name='def';
		$model->add();

		//查询并返回一个object 此时model==一开始获得的有值的model
		$model = Users::factory()->where(['name' => ['LIKE', '%abc%'])->find();

		//查询并返回一个object的集合
		$model_list = Users::factory()->where(['name' => ['LIKE', '%abc%'])->find_all();
		foreach ($model_list as $model)
		{
			//$model == 一开始获得的有值的model
		}

	[9. Controller建立]
		在controllers目录下创建welcome.php
		创建方法action_index
		此时当用户访问http://your_host/或http://your_host/welcome/index时,会调用此方法执行

	[10. 使用View]
		//如果该controller继承了Simple\Controller\Template
		//设置变量
		$this->bind('title', 'this is title');
		//绑定模板文件 app/views/welcome/index.php
		$this->template = 'welcome/index';
		//输出
		$this->display()
		//模板文件中可以直接使用$title得到您绑定的变量

## 常用操作

	[数组操作]
		use \Simple\Arr;
		//$row = isset($array['test']) ? $array['test'] : false;
		$row = Arr::get($array, 'test', false);

	[DB Build]
		use \Simple\Build\SELECT;
		//查找articles表中,id=3的一条数据
		SELECT::table('articles')->where(3)->find();
		//查找articles表中,id!=3且type=1的一条数据
		SELECT::table('articles')->where(['id' => ['!=', 3], 'type' => 1])->find();
		//查找slave DB中的articles表中,id in (1, 2, 3)且type=1的所有数据,按id ASC排序,获取从第五条开始的10条数据  WHERE条件中的IN/NOT IN/BETWEEN必须为大写
		SELECT::table('slave.articles')->where(['id' => ['IN', [1, 2, 3]], 'type' => 1])->order(['id' => 'ASC'])->limit(5, 10)->find_all();

		use \Simple\Build\UPDATE;
		//修改articles表中,id=3的数据,修改title为new_title,content为new_content		SELF::INC自增 SELF::DEC自减  SELF::*2自乘2
		UPDATE('articles')->where(3)->save(['title' => 'new_title', 'content' => 'new_content', 'read_count' => 'SELF::INC']);

		use \Simple\Build\INSERT;
		//新增
		$id = INSERT::table('articles')->add(['title' => 'new_title', 'content' => 'new_content']);
		//新增一批
		$first_id = INSERT::table('articles')->add([['title' => 'new_title1', 'content' => 'new_content1'], ['title' => 'new_title2', 'content' => 'new_content2']]);

		use \Simple\Build\DELETE;
		//删除
		DELETE::table('articles')->where(3)->del();

	[Config操作]
		use \Simple\Config;
		//读取database文件
		$database = Config::get('database');
		//读取database.php中的master节点
		$master = Config::get('database.master');
		//只支持以上两种方式 不支持三级节点

	[Controller]
		//在继承于controller中使用 以下三个函数后两个参数可省略
		//获取$_GET['user_id']值,并用intval函数过滤,如果$_GET['user_id']不存在,则返回0
		$this->_get('user_id', 'intval', 0);
		//获取$_POST['nickname']值,并用trim过滤空格,如果$_POST['nickname']不存在,则返回none
		$this->_post('nickname', 'trim', 'none');
		//不限制是post或get,有限读post
		$this->_r('abc', 'trim', 0);

		//在继承于controller\template中使用
		//指定模板文件为 app/views/welcome/abc.php 不设置则默认为app/views/controller名/action名.php
		$this->_template = 'welcome/abc';
		//绑定变量 支持以下两种方式绑定 可在模板中直接使用$title $body获取该值
		$this->bind('title', 'this is title');
		$this->bind([
			'title'	=> 'this is title',
			'body'	=> 'this is body',
		]);
		//跳转
		$this->redirect('www.qq.com', 301);


	[Curl操作]
		use \Simple\Curl;
		/*默认的curl option值
		[
			CURLOPT_SSL_VERIFYPEER	=>	FALSE,
			CURLOPT_SSL_VERIFYHOST	=>	FALSE,
			CURLOPT_AUTOREFERER		=>	FALSE,
			CURLOPT_HEADER			=>	FALSE,
			CURLOPT_RETURNTRANSFER	=>	TRUE,
			CURLOPT_FOLLOWLOCATION	=>	TRUE,
			CURLOPT_CONNECTTIMEOUT	=>	3,
			CURLOPT_TIMEOUT			=>	3,
			CURLOPT_USERAGENT		=>	'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.1 (KHTML, like Gecko) Chrome/6.0.440.0 Safari/534.1',
			CURLOPT_ENCODING		=>	'gzip,deflate',
			CURLOPT_HTTPHEADER		=>	['Connection: Keep-Alive'],
		];*/
		//GET
		$obj = Curl::factory('http://www.baidu.com')->get([
			'id'	=> 1,
			'abc'	=> 'def',
		]);
		//POST
		$obj = Curl::factory('http://www.baidu.com')->post([
			'id'	=> 1,
			'abc'	=> 'def',
		]);
		//传递的参数不需要build
		$obj = Curl::factory('http://www.baidu.com')->post('id=1&abc=def', false);
		//设置referer
		$obj = Curl::factory('http://www.baidu.com')->referer('www.qq.com')->post('id=1&abc=def', false);
		//设置cookie
		$obj = Curl::factory('http://www.baidu.com')->referer('www.qq.com')->cookie('/data/www/abcdef.cookie')->post('id=1&abc=def', false);
		//设置代理
		$obj = Curl::factory('http://www.baidu.com')->referer('www.qq.com')->proxy('127.0.0.1', '8080')->post('id=1&abc=def', false);
		//获取正文
		$body = $obj->body();
		//获取返回头
		$header = $obj->header();
		//省心缩写
		$body = Curl::factory('http://www.baidu.com')->post([
			'id'	=> 1,
			'abc'	=> 'def',
		])->body();
		//手写curl option调用
		$result = Curl::single($option);
		//并发调用
		$options = [$option1, $option2, ... ]
		$result = Curl::multi($options);

	[Cookie操作]
		use \Simple\Cookie;
		//增/改
		Cookie::set('abc', 'hahaha');
		//查
		$a = Cookie::get('abc');
		//删
		Cookie::delete('abc');
		//清空
		Cookie::clear();

	[DB操作]
		use \Simple\DB;
		//操作master
		//执行sql
		$query = DB::instance()->query($sql);
		//获取一条结果集 数组
		$row = DB::instance()->fetch($query);
		//获取所有返回值 数组
		$list = DB::instance()->fetch_all($query);
		//获取插入数据的insert_id
		$insert_id = DB::instance()->insert_id();
		//数据过滤 real_escape_string
		$value = DB::instance()->check($value);
		//操作slave
		$query = DB::instance('salve')->query($sql);

	[HTTP操作]
		use \Simple\HTTP;
		//设置一条header
		HTTP::instance()->header($key, $value);
		//设置返回状态码
		HTTP::instance()->status(404);
		//跳转某url
		HTTP::instance()->redirect('www.qq.com');
		//输出json并结束 会自动将data转为json格式
		HTTP::instance()->ajax($data);
		//输出正文并结束
		HTTP::instance()->finish($data);

	[Model操作]
		//创建test.php extends ORM,并设置$table='test',$pk='id'

		//获取一个uid=3的object
		$model = Users::factory(3);
		//修改该model的值,并使之同步到数据库 接上述
		$model->name='abc';
		$model->save();

		//获取一个空的object
		$model = Users::factory();
		//新增一条数据 接上述
		$model->uid=3;
		$model->name='def';
		$model->add();

		//查询并返回一个object 此时model==一开始获得的有值的model
		$model = Users::factory()->where(['name' => ['LIKE', '%abc%'])->find();

		//查询并返回一个object的集合
		$model_list = Users::factory()->where(['name' => ['LIKE', '%abc%'])->find_all();
		foreach ($model_list as $model)
		{
			//$model == 一开始获得的有值的model
		}

	[Redis操作]
		use \Simple\Redis;
		//获取
		Redis::instance()->get($this->_prefix.$this->_session_id);
		
	[Route操作]
		use \Simple\Route;
		在app/init.php中配置路由
		//访问www.simple.com时进入welcome controller和action_index
		Route::add('', [
			'controller'	=> 'welcome',
			'action'		=> 'index',
		]);
		//访问www.simple.com/index时,进入welcome controller中的action_index方法
		Route::add('index', [
			'controller'	=> 'welcome',
			'action'		=> 'index',
		]);
		//访问www.simple.com/articles/1234类似的网址,进入articles controller的action_detail方法,并传入参数1234
		Route::add('articles/(\d+)', [
			'controller'	=> 'articles',
			'action'		=> 'detail',
			'param'			=> ':1',
		]);
		//自定义404 当404时,使用welcome controller中的action_404方法处理
		Route::set_404('welcome', '404');

	[Session操作]
		use \Simple\Session;
		//增/改
		Session::instance()->set('abc', 'edf');
		//查
		Session::instance()->get('abc');
		//删
		Session::instance()->delete('abc');
		//清空
		Session::instance()->clear();

	[VIEW操作]
		use \Simple\View;
		//绑定变量
		View::bind('test', $abc);
		//绑定数组 按key区分变量
		View::bind([
			'test'	=> $abc,
			'title'	=> $title,
		]);
		//输出模板app/views/index.php
		View::display('index');

##压测结果

	#php7环境下，2.5GHZi7 16G内存 未开启opcache
	#siege -c 500 -t 5s http://test/
	Lifting the server siege...
	Transactions:				7748 hits
	Availability:				92.69 %
	Elapsed time:				4.97 secs
	Data transferred:			0.09 MB
	Response time:				0.01 secs
	Transaction rate:			1558.95 trans/sec
	Throughput:					0.02 MB/sec
	Concurrency:				15.78
	Successful transactions:	7748
	Failed transactions:		611
	Longest transaction:		0.18
	Shortest transaction:		0.00