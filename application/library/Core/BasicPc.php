<?php
/**
 * File: BasicPc.php
 * Functionality: Basic Controller(再整理)
 * Author: 资料空白
 * Date: 2016-3-8
 */

class PcBasicController extends BasicController
{
	protected $uinfo=array();
	protected $userid=0;
	protected $login=FALSE;
	public $serverPrivateKey = "MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAKrU5gne1HvK18yk9aFX+LIgf8bIZvW/TgAAQWUkLkVDf1s91r6JmlmJsvGDz1KWuFEtU5k+ZTY+znh0ncLfgdTcmVvymp1D4fhEKt/JSaZNZe7Fb3kfl7iT15pQBivirrkpP1dwyM5EzafkRo5wKOktbQLYglW/e+ChVf4L+mqXAgMBAAECgYBcweb6Wwzi/rv4OWXKKps2FSFsTSpiq3Jt27WmdmPNZh4D6+rrYIn3riYEr35mKMKCCWuIHPIV5zpy+1ciFfxHNifvwVs9zpWGYkuvyI2Ar41zODI8doYFaQjWUBf/xJziabTEn1pFsH+Q8xWqr0fXdFdKYt6lYnjZR3bJIL79yQJBANaEQ0MqPqbj4s6L++igcgizkPOQ00a0kRdv6R0wQWqXg5fseg776sUv301XYbTnc7BlmHsQUQsYcROOqzhZlNsCQQDL3f2ehMGecX2qnImBGbXIRIIF1DnjULDzBpz/ijMYg1trIRRjBirWFj6cQOEOxlW2A8qpz1ZxR9zfSzjYXG/1AkBPn8xvs9CJlfDsBd29XUC2piBZqBokFoX8kxeONAk0DYVU8Pvlb/CWvMxAIv0rbvXsNenBVC8g1TOztLMtOWMdAkEAgC1ZyXHknm7yuPNkzOPSVFEmgu21W8OfDZ2p1k0Y5R+puch5ne0Bv8sKoIl2NyjiOOdXY761tdGeAFK2MeqkhQJALGjfBtrV9c3u3XVVbpASadkkOcUvXOb8fyRvTv03Bg3cbF3hP6ucb5SPEg6dDHixRj25S+JTiYH5WxbtyYni5g==";
  	
	public function init(){	
		parent::init();
		$sysvars = $data = array();
		$this->config=$this->load('config')->getConfig();
		if((isset($this->config['web_name']) AND strlen($this->config['web_name'])>0)==false){
			$this->config['web_name'] = WEB_NAME;
		}
		$data['config']= $this->config;
		$sysvars['isHttps']=$this->isHttps=isHttps();
		$sysvars['isAjax']=$this->isAjax=isAjax();
		$sysvars['isGet']=$this->isGet=isGet();
		$sysvars['isPost']=$this->isPost=isPost();
		$sysvars['currentUrl']=stripHTML(str_replace('//', '/',$_SERVER['REQUEST_URI']));
		$sysvars['currentUrlSign']=md5(URL_KEY.$sysvars['currentUrl']);
		$data['sysvars']=$sysvars; 
        $uinfo = $this->getSession('uinfo');
		if(is_array($uinfo) AND !empty($uinfo) AND $uinfo['expiretime']>time()){
			$groupName=$this->load('user_group')->getConfig();
			$uinfo['groupName'] = $groupName[$uinfo['groupid']];
			$uinfo['expiretime'] = time() + 15*60;
			$this->setSession('uinfo',$uinfo);
			$data['login']=$this->login=true;
			$data['uinfo']= $this->uinfo=$uinfo;
			$this->userid=$uinfo['id'];
		}else{
			$data['login']=$this->login=false;
			$this->unsetSession('uinfo');
		}
		//防csrf攻击
		$data['csrf_token'] = $this->createCsrfToken();
        $this->getView()->assign($data);
	}

    //生成JWT token
    public function createToken()
    {
        $tokenKey = array(
            "iss" => "http://zlkb.net",  //jwt签发者
            "aud" => 'RPC',                     //接收jwt的一方
            "exp" => time() + 60,               //过期时间
        );
        return JWT::encode($tokenKey, self::readRSAKey($this->serverPrivateKey), 'RS256');
    }
    //为JWT准备的，证书处理函数
    private static function readRSAKey($key)
    {
        $isPrivate = strlen($key) > 500;
        if ($isPrivate) {
            $lastKey = chunk_split($key, 64, "\n");
            $lastKey = "-----BEGIN RSA PRIVATE KEY-----\n" . $lastKey . "-----END RSA PRIVATE KEY-----\n";
            return $lastKey;
        } else {
            $lastKey = chunk_split($key, 64, "\n");
            $lastKey = "-----BEGIN PUBLIC KEY-----\n" . $lastKey . "-----END PUBLIC KEY-----\n";
            return $lastKey;
        }
    }	
	public function show_message($code='',$msg='',$url='/'){
		$this->forward("Index",'Showmsg','index',array('code'=>$code,'msg'=>$msg,'url'=>$url));
		return FALSE; 
	}
	
	//生成csrftoken　防csrf攻击
    private function createCsrfToken(){
    	$csrf_token=$this->getSession('csrf_token');
    	if(!$csrf_token){
    		$csrf_token=$this->createToken(); 
			$this->setSession('csrf_token',$csrf_token);
    	}
		return $csrf_token;
	}
	//验证csrftoken 防csrf攻击
	public function VerifyCsrfToken($csrf_token=''){
		$csrf_token=$csrf_token?$csrf_token:$this->getPost('csrf_token',false);
		$session_csrf_token=$this->getSession('csrf_token',false); 
		if($session_csrf_token && $session_csrf_token==$csrf_token){
			if(!isAjax()){
				$this->setSession('csrf_token','');
			}
			return true;
		}else{
			return false;
		}
	}
}