<?php
/**
 * The html template file of index method of upgrade module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2013 青岛易软天创网络科技有限公司 (QingDao Nature Easy Soft Network Technology Co,LTD www.cnezsoft.com)
 * @license     LGPL (http://www.gnu.org/licenses/lgpl.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     upgrade
 * @version     $Id: index.html.php 4129 2013-01-18 01:58:14Z wwccss $
 */
?>
<?php include '../../common/view/header.lite.html.php';?>
<table align='center' class='table-5 f-14px'>
  <caption><?php echo $lang->upgrade->warnning;?></caption>
  <tr>
    <td><?php echo $lang->upgrade->warnningContent;?></td>
  </tr>
  <tr>
    <td colspan='2' class='a-center'><?php echo html::linkButton($lang->upgrade->common, inlink('selectVersion'));?></td>
  </tr>
</table>
<?php include '../../common/view/footer.lite.html.php';?>
