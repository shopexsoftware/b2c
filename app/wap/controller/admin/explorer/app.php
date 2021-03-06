<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class wap_ctl_admin_explorer_app extends desktop_controller
{
    /*
     * workground
     * @var string
     */
    var $workground = 'wap_ctl_admin_explorer_app';

    /*
     * @param object $app
     */
    function __construct($app)
    {
        $request_params = array(
            'app_id' => $_GET['app_id'],
            'content_path' => $_GET['content_path'],
            'open_path' => $_GET['open_path'],
            'file_name' => $_GET['file_name'],
            'post_app_id' => $_POST['app_id'],
            'post_content_path' => $_POST['content_path'],
            'post_open_path' => $_POST['open_path'],
            'post_file_name' => $_POST['file_name'],
            'post_file_source' => $_POST['file_source'],
        );
        foreach($request_params as $request_param)
        {
            if( isset($request_param) && is_string($request_param) && $request_param != str_replace('../','',$request_param) )
            {
                $this->_error('参数格式错误，不应该包含‘../’');
            }
        }
        parent::__construct($app);
        $this->_request = kernel::single('base_component_request');
        $this->_response = kernel::single('base_component_response');
    }//End Function

    /*
     * app::get('wap')->_(验证是否可被编辑)
     * @param string $app_id
     * @param string $content_path
     * @return boolean
     *
     */
    private function check($app_id, $content_path)
    {
        return app::get('wap')->model('explorers')->select()->columns('id')->where('app = ?', $app_id)->where('path = ?', str_replace('-', '/', $content_path))->instance()->fetch_one() ? true : false;
    }//End Function

    /*
     * app::get('wap')->_(app模版目录)
     */
    public function index()
    {
        $this->finder('wap_mdl_explorers', array(
            'title' => app::get('wap')->_('APP资源管理'),
            'use_buildin_set_tag' => false,
            'use_buildin_recycle' => false,
            'use_buildin_export' => false,
        ));
    }//End Function

    /*
     * app::get('wap')->_(目录浏览)
     */
    public function directory()
    {
        $app_id = $this->_request->get_get('app_id');
        $content_path = $this->_request->get_get('content_path');
        $open_path = trim($this->_request->get_get('open_path'));

        if(!$this->check($app_id, $content_path))
            $this->_redirect('index.php?app=wap&ctl=admin_explorer_app&act=index');  //app::get('wap')->_(验证是否可以浏览)

        $fileObj = kernel::single('wap_explorer_file');
        $dir = realpath(APP_DIR . '/' . $app_id . '/' . str_replace('-', '/', $content_path) . '/' . str_replace(array('-','.'), array('/','/'), $open_path));   //open_pathapp::get('wap')->_(不允许有)'./'&'../'
        $filter=array(
                 'id' => $app_id,
                 'dir' => $dir,
                 'show_bak' => false,
                 'type' => 'all'
             );
        $file = $fileObj->file_list($filter);
        $file = $fileObj->parse_filter($file);
        $this->pagedata['file'] = array_reverse($file);
        $this->pagedata['url'] = sprintf('index.php?app=%s&ctl=%s&act=%s&app_id=%s&content_path=%s',
            $this->_request->get_get('app'),
            $this->_request->get_get('ctl'),
            $this->_request->get_get('act'),
            $this->_request->get_get('app_id'),
            $this->_request->get_get('content_path')
        );
        $this->pagedata['app_id'] = $app_id;
        $this->pagedata['content_path'] = $content_path;
        $this->pagedata['open_path'] = $open_path;
        $this->pagedata['last_path'] = strrpos($open_path, '-') ? substr($open_path, 0, strrpos($open_path, '-')) : ($open_path ? ' ' : '');
        $this->page('admin/explorer/app/directory.html');
    }//End Function

    /*
     * app::get('wap')->_(文件详情)
     */
    public function detail()
    {
        $app_id = $this->_request->get_get('app_id');
        $content_path = $this->_request->get_get('content_path');
        $open_path = $this->_request->get_get('open_path');
        $file_name = $this->_request->get_get('file_name');

        if(!$this->check($app_id, $content_path))   $this->_error();
        $fileObj = kernel::single('wap_explorer_file');
        $dir = realpath(APP_DIR . '/' . $app_id . '/' . str_replace('-', '/', $content_path) . '/' . str_replace(array('-','.'), array('/','/'), $open_path));   //open_pathapp::get('wap')->_(不允许有)'./'&'../'
        $filter=array(
                 'id' => $app_id,
                 'dir' => $dir,
                 'show_bak' => true,
                 'type' => 'all'
             );
        $filenameInfo = pathinfo($file_name);
        $this->pagedata['file_baklist'] = $fileObj->get_file_baklist($filter, $file_name);
        $this->pagedata['app_id'] = $app_id;
        $this->pagedata['content_path'] = $content_path;
        $this->pagedata['open_path'] = $open_path;
        $this->pagedata['file_name'] = $file_name;
        if(in_array($filenameInfo['extension'], array('css', 'html', 'js', 'xml'))){
            $this->pagedata['file_content']  = $fileObj->get_file($dir . '/' . $file_name);
            $this->display('admin/explorer/app/tpl_source.html');
        }else{
            $this->pagedata['file_url'] = kernel::base_url(1) .  rtrim(str_replace('//', '/', '/app/' . $app_id . '/' . str_replace('-', '/', $content_path) . '/' . str_replace(array('-','.'), array('/','/'), $open_path) . '/' . $file_name));
            $this->display('admin/explorer/app/tpl_image.html');
        }
    }//End Function

    /*
     * app::get('wap')->_(保存文件)
     */
    public function svae_source()
    {
        $this->begin();
        $app_id = $this->_request->get_post('app_id');
        $content_path = $this->_request->get_post('content_path');
        $open_path = $this->_request->get_post('open_path');
        $file_name = $this->_request->get_post('file_name');

        if(!$this->check($app_id, $content_path))   $this->_error();

        $has_bak = ($this->_request->get_post('has_bak')) ? true : false;
        $file_source = $this->_request->get_post('file_source');

        $fileObj = kernel::single('wap_explorer_file');
        $dir = realpath(APP_DIR . '/' . $app_id . '/' . str_replace('-', '/', $content_path) . '/' . str_replace(array('-','.'), array('/','/'), $open_path));   //open_pathapp::get('wap')->_(不允许有)'./'&'../'
        if($has_bak){
            $fileObj->backup_file($dir . '/' . $file_name);
        }
        $fileObj->save_source($dir . '/' . $file_name, $file_source);
        $this->end(true, app::get('wap')->_('保存成功'));
    }//End Function

    /*
     *app::get('wap')->_( 保存图片文件)
     */
    public function save_image()
    {
        $this->begin();
        $app_id = $this->_request->get_post('app_id');
        $content_path = $this->_request->get_post('content_path');
        $open_path = $this->_request->get_post('open_path');
        $file_name = $this->_request->get_post('file_name');

        if(!$this->check($app_id, $content_path))   $this->_error();

        $has_bak = ($this->_request->get_post('has_bak')) ? true : false;

        $fileObj = kernel::single('wap_explorer_file');
        $dir = realpath(APP_DIR . '/' . $app_id . '/' . str_replace('-', '/', $content_path) . '/' . str_replace(array('-','.'), array('/','/'), $open_path));   //open_pathapp::get('wap')->_(不允许有)'./'&'../'
        if($has_bak){
            $fileObj->backup_file($dir . '/' . $file_name);
        }
        $fileObj->save_image($dir . '/' . $file_name, $_FILES['upfile']);
        $this->end(true, app::get('wap')->_('保存成功'));
    }//End Function

    /*
     * app::get('wap')->_(删除文件)
     */
    public function delete_file()
    {
        $this->begin();
        $app_id = $this->_request->get_get('app_id');
        $content_path = $this->_request->get_get('content_path');
        $open_path = $this->_request->get_get('open_path');
        $file_name = $this->_request->get_get('file_name');

        if(!$this->check($app_id, $content_path))   $this->_error();

        $dir = realpath(APP_DIR . '/' . $app_id . '/' . str_replace('-', '/', $content_path) . '/' . str_replace(array('-','.'), array('/','/'), $open_path));
        $fileObj = kernel::single('wap_explorer_file');
        $fileObj->delete_file($dir . '/' . $file_name);
        $this->end(true, app::get('wap')->_('删除成功'));
    }//End Function

    /*
     * app::get('wap')->_(恢复文件)
     */
    public function recover_file()
    {
        $this->begin();
        $app_id = $this->_request->get_get('app_id');
        $content_path = $this->_request->get_get('content_path');
        $open_path = $this->_request->get_get('open_path');
        $file_name = $this->_request->get_get('file_name');

        if(!$this->check($app_id, $content_path))   $this->_error();

        $dir = realpath(APP_DIR . '/' . $app_id . '/' . str_replace('-', '/', $content_path) . '/' . str_replace(array('-','.'), array('/','/'), $open_path));

        $fileObj = kernel::single('wap_explorer_file');
        $fileObj->recover_file($dir . '/' . $file_name);
        $this->end(true, app::get('wap')->_('恢复成功'));
    }//End Function

}//End Class
