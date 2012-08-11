<?php

/*
 * 分发的 HTTP 接口
 *
 */
define ("CMD_ADD_FILE", 'add'); // 增加单个文件
define ("CMD_DEL_FILE", 'del'); // 删除单个文件
define ("CMD_ADD_TGZ", 'tgz');  // 批量压缩增加 
define ("CMD_DEPLOY_PKG", 'pkg'); // 部署程序包

class SOSO_HttpDist {
    var $post_url;

    var $cmd_type;
    var $local_path;
    var $remote_path;
    var $svc_name;

    var $filesize = 0;
    var $debug = 0;
    var $last_err_msg = "";

    function __construct($url = "http://localhost:8080")
    {
        $this->post_url = $url;
    }

    function set_debug($debug = 0)
    {
        $this->debug = ($debug > 0) ? 1 : 0;
    }

    function dist($cmd_type, $svc_name, $remote_path, $local_path)
    {
        $this->cmd_type = $cmd_type;
        $this->svc_name = $svc_name;
        $this->remote_path = $remote_path;
        $this->local_path = $local_path;

        return $this->do_dist();
    }

    function get_last_err()
    {
        return $this->last_err_msg;
    }

    function getmicrotime()
    {
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }

    function do_dist()
    {
        // build post data
        $post_data = array();
        $post_data['svc'] = $this->svc_name;
        $post_data['type'] = $this->cmd_type;
        $post_data['path'] = $this->remote_path;

        if ($this->cmd_type != CMD_DEL_FILE)
        {
            $post_data['md5sum'] = md5_file ($this->local_path);
            $post_data['data'] = '@' . $this->local_path;

            if ($this->debug != 0)
            {
                $this->filesize = filesize($this->local_path);
            }
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->post_url); 
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);
        curl_setopt($ch, CURLOPT_HEADER, $this->debug);

        if ($this->debug != 0)
        {
            echo "post data:\n";
            print_r ($post_data);
            $time_start = $this->getmicrotime();
        }

        $postResult = curl_exec($ch);

        if ($this->debug != 0)
        {
            $time_end = $this->getmicrotime();
            $time_diff = $time_end - $time_start;
        }

        if (curl_errno($ch)) 
        {
            $this->last_err_msg = curl_error($ch);
            return curl_errno($ch);
        }

        if ($this->debug != 0)
        {
            echo $postResult . "\n";
            echo "post filesize: " . $this->filesize . "\n";
	    echo "post takes $time_diff seconds.\n";
	}
	else    
	{       
		if ( (strlen($postResult) >= 2) && substr_compare($postResult, "-1", 0, 2) == 0 )
		{       
			return -1;
		}       
	} 

        // clean up
        curl_close($ch);

        return 0;
    }
}

// vim: se ft=php sw=4 et
?>
