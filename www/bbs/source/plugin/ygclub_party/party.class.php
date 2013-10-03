<?php

/** 
 *   [ygclub.org] (C)北京LEAD阳光志愿者俱乐部
 *
 *   @author leeyupeng
 *   @email  leeyupeng@gmail.com
 */

include_once('thread.class.php');

class plugin_ygclub_party {
    public function __construct() {
        $this->plugin_ygclub_party();
    }

    public function plugin_ygclub_party() {
    }
}

class plugin_ygclub_party_forum extends plugin_ygclub_party {

    function viewthread_postbottom() {
        global $_G;
        include_once template('ygclub_party:party_info');
        include_once template('ygclub_party:party_sign');

        $party_thread = new threadplugin_ygclub_party();
        $party = $party_thread->_getpartyinfo($_G['tid']);
        if(!$party['tid']) return '';

        $partyer = C::t('#ygclub_party#partyers')->fetch_by_uid_tid($_G['uid'], $party['tid']);

        $condata = $party_thread->_load_forumparty_condata($_G['fid']);
        return array(0=>tpl_ygclub_party_partyers_list() . tpl_ygclub_party_sign());
    }

    function viewthread_title_extra() {
        global $_G;
        $tmp = C::t('#ygclub_party#party')->fetch_by_ctid($_G['tid']);
        if($tmp['tid'] > 0)
        {
            return "<a href='forum.php?mod=viewthread&tid={$tmp[tid]}' style='color:#C30A00'>[相关召集帖]</a>";
        }
        else
        {
            $tmp = C::t('#ygclub_party#party')->fetch_ctid($_G['tid']);
            if($tmp['ctid'] > 0)
            {
                return "<a href='forum.php?mod=viewthread&tid={$tmp[ctid]}' style='color:#2d6e00'>[相关总结帖]</a>";
            }
        }
        return '';
    }

}
?>
