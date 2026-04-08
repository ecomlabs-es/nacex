<?php
class ROnacexlogs{
    private $header;

    public function __construct(){
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'VInacexlogs.php');
        require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'nacexutils.php');
        $this->header = VInacexlogs::header();
    }
    public function init_delete_refresh ($_file = "none"){
        $_nacexutils = new nacexutils();
        $_result=$_file!="none"?$_nacexutils->delete_file($_file):"";
        $_result.=$_nacexutils->content_directory();
        $this->response($this->header,$_result,600);
    }
    public function read($_file){
        $_nacexutils = new nacexutils();
        $_result=$_nacexutils->read_file($_file);
        $this->response($this->header,$_result,601);
    }
    private function response ($_header,$_result,$_code){
        $_response = array();
        array_push($_response,
            array(  'cod_response' => $_code,
                    'header' => $_header,
                    'result' => $_result
            ));
        echo json_encode($_response);
    }
}

