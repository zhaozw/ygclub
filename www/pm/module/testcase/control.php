<?php
/**
 * The control file of case currentModule of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2013 青岛易软天创网络科技有限公司 (QingDao Nature Easy Soft Network Technology Co,LTD www.cnezsoft.com)
 * @license     LGPL (http://www.gnu.org/licenses/lgpl.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     case
 * @version     $Id: control.php 4515 2013-03-02 06:46:34Z wwccss $
 * @link        http://www.zentao.net
 */
class testcase extends control
{
    public $products = array();

    /**
     * Construct function, load product, tree, user auto.
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->loadModel('product');
        $this->loadModel('tree');
        $this->loadModel('user');
        $this->view->products = $this->products = $this->product->getPairs();
    }

    /**
     * Index page.
     * 
     * @access public
     * @return void
     */
    public function index()
    {
        $this->locate($this->createLink('testcase', 'browse'));
    }

    /**
     * Browse cases.
     * 
     * @param  int    $productID 
     * @param  string $browseType 
     * @param  int    $param 
     * @param  string $orderBy 
     * @param  int    $recTotal 
     * @param  int    $recPerPage 
     * @param  int    $pageID 
     * @access public
     * @return void
     */
    public function browse($productID = 0, $browseType = 'byModule', $param = 0, $orderBy = 'id_desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        /* Set browseType, productID, moduleID and queryID. */
        $browseType = strtolower($browseType);
        $productID = $this->product->saveState($productID, $this->products);
        $moduleID  = ($browseType == 'bymodule') ? (int)$param : 0;
        $queryID   = ($browseType == 'bysearch') ? (int)$param : 0;

        /* Set menu, save session. */
        $this->testcase->setMenu($this->products, $productID);
        $this->session->set('caseList', $this->app->getURI(true));
        $this->session->set('productID', $productID);
        $this->session->set('moduleID', $moduleID);
        $this->session->set('browseType', $browseType);
        $this->session->set('orderBy', $orderBy);

        /* Load lang. */
        $this->app->loadLang('testtask');

        /* Load pager. */
        $this->app->loadClass('pager', $static = true);
        $pager = pager::init($recTotal, $recPerPage, $pageID);

        /* By module or all cases. */
        if($browseType == 'bymodule' or $browseType == 'all')
        {
            $childModuleIds    = $this->tree->getAllChildId($moduleID);
            $this->view->cases = $this->testcase->getModuleCases($productID, $childModuleIds, $orderBy, $pager);
        }
        /* Cases need confirmed. */
        elseif($browseType == 'needconfirm')
        {
            $this->view->cases = $this->dao->select('t1.*, t2.title AS storyTitle')->from(TABLE_CASE)->alias('t1')->leftJoin(TABLE_STORY)->alias('t2')->on('t1.story = t2.id')
                ->where("t2.status = 'active'")
                ->andWhere('t1.deleted')->eq(0)
                ->andWhere('t2.version > t1.storyVersion')
                ->orderBy($orderBy)
                ->page($pager)
                ->fetchAll();
        }
        /* By search. */
        elseif($browseType == 'bysearch')
        {
            if($queryID)
            {
                $query = $this->loadModel('search')->getQuery($queryID);
                if($query)
                {
                    $this->session->set('testcaseQuery', $query->sql);
                    $this->session->set('testcaseForm', $query->form);
                }
                else
                {
                    $this->session->set('testcaseQuery', ' 1 = 1');
                }
            }
            else
            {
                if($this->session->testcaseQuery == false) $this->session->set('testcaseQuery', ' 1 = 1');
            }

            $caseQuery = str_replace("`product` = 'all'", '1', $this->session->testcaseQuery); // If product is all, change it to 1=1.
            $caseQuery = $this->loadModel('search')->replaceDynamic($caseQuery);
            $this->view->cases = $this->dao->select('*')->from(TABLE_CASE)->where($caseQuery)
                ->andWhere('product')->eq($productID)
                ->andWhere('deleted')->eq(0)
                ->orderBy($orderBy)->page($pager)->fetchAll();
        }

        /* save session .*/
        $this->loadModel('common')->saveQueryCondition($this->dao->get(), 'testcase', $browseType == 'needconfirm' ? false : true);

        /* Build the search form. */
        $this->config->testcase->search['params']['product']['values']= array($productID => $this->products[$productID], 'all' => $this->lang->testcase->allProduct);
        $this->config->testcase->search['params']['module']['values'] = $this->loadModel('tree')->getOptionMenu($productID, $viewType = 'case');
        $this->config->testcase->search['actionURL'] = $this->createLink('testcase', 'browse', "productID=$productID&browseType=bySearch&queryID=myQueryID");
        $this->config->testcase->search['queryID']   = $queryID;
        $this->loadModel('search')->setSearchParams($this->config->testcase->search);

        /* Assign. */
        $this->view->title         = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->common;
        $this->view->position[]    = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
        $this->view->position[]    = $this->lang->testcase->common;
        $this->view->productID     = $productID;
        $this->view->productName   = $this->products[$productID];
        $this->view->moduleTree    = $this->tree->getTreeMenu($productID, $viewType = 'case', $startModuleID = 0, array('treeModel', 'createCaseLink'));
        $this->view->moduleID      = $moduleID;
        $this->view->pager         = $pager;
        $this->view->users         = $this->user->getPairs('noletter');
        $this->view->orderBy       = $orderBy;
        $this->view->browseType    = $browseType;
        $this->view->param         = $param;
        $this->view->treeClass     = $browseType == 'bymodule' ? '' : 'hidden';

        $this->display();
    }

    /**
     * Create a test case.
     * 
     * @param  int    $productID 
     * @param  int    $moduleID 
     * @param  string $from
     * @param  int    $param
     * @access public
     * @return void
     */
    public function create($productID, $moduleID = 0, $from = '', $param = 0, $storyID = 0)
    {
        $testcaseID = $from == 'testcase' ? $param : 0;
        $bugID      = $from == 'bug' ? $param : 0;
        
        $this->loadModel('story');
        if(!empty($_POST))
        {
            $caseID = $this->testcase->create($bugID);
            if(dao::isError()) die(js::error(dao::getError()));
            $this->loadModel('action');
            $this->action->create('case', $caseID, 'Opened');
            die(js::locate($this->createLink('testcase', 'browse', "productID=$_POST[product]&browseType=byModule&param=$_POST[module]"), 'parent'));
        }
        if(empty($this->products)) $this->locate($this->createLink('product', 'create'));

        /* Set productID and currentModuleID. */
        $productID       = $this->product->saveState($productID, $this->products);
        $currentModuleID = (int)$moduleID;

        /* Set menu. */
        $this->testcase->setMenu($this->products, $productID);

        /* Init vars. */
        $type         = 'feature';
        $stage        = '';
        $pri          = 0;
        $caseTitle    = '';
        $precondition = '';
        $keywords     = '';
        $steps        = array();

        /* If testcaseID large than 0, use this testcase as template. */
        if($testcaseID > 0)
        {
            $testcase     = $this->testcase->getById($testcaseID);
            $productID    = $testcase->product;
            $type         = $testcase->type ? $testcase->type : 'feature';
            $stage        = $testcase->stage;
            $pri          = $testcase->pri;
            $storyID      = $testcase->story;
            $caseTitle    = $testcase->title;
            $precondition = $testcase->precondition;
            $keywords     = $testcase->keywords;
            $steps        = $testcase->steps;
        }
               
        /* If bugID large than 0, use this bug as template. */
        if($bugID > 0)
        {
            $bug      = $this->loadModel('bug')->getById($bugID);
            $type     = $bug->type;
            $pri      = $bug->pri ? $bug->pri : $bug->severity;
            $storyID  = $bug->story;
            $caseTitle= $bug->title;
            $keywords = $bug->keywords;
            $steps    = $this->testcase->createStepsFromBug($bug->steps);
        }
       
        /* Padding the steps to the default steps count. */
        if(count($steps) < $this->config->testcase->defaultSteps)
        {
            $paddingCount = $this->config->testcase->defaultSteps - count($steps);
            $step = new stdclass();
            $step->desc   = '';
            $step->expect = '';
            for($i = 1; $i <= $paddingCount; $i ++) $steps[] = $step;
        }

        $title      = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->create;
        $position[] = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
        $position[] = $this->lang->testcase->create;

        $users = $this->user->getPairs();
        $this->view->title            = $title;
        $this->view->caseTitle        = $caseTitle;
        $this->view->position         = $position;
        $this->view->productID        = $productID;
        $this->view->users            = $users;           
        $this->view->productName      = $this->products[$productID];
        $this->view->moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0);
        $this->view->currentModuleID  = $currentModuleID;
        $this->view->stories          = $this->story->getProductStoryPairs($productID);
        $this->view->type             = $type;
        $this->view->stage            = $stage;
        $this->view->pri              = $pri;
        $this->view->storyID          = $storyID;
        $this->view->title            = $title;
        $this->view->precondition     = $precondition;
        $this->view->keywords         = $keywords;
        $this->view->steps            = $steps;

        $this->display();
    }

   
    /**
     * Create a batch test case.
     * 
     * @param  int   $productID 
     * @param  int   $moduleID 
     * @param  int   $testcaseID
     * @access public
     * @return void
     */
    public function batchCreate($productID, $moduleID = 0)
    {
        $this->loadModel('story');
        if(!empty($_POST))
        {
            $caseID = $this->testcase->batchCreate($productID);
            if(dao::isError()) die(js::error(dao::getError()));
            die(js::locate($this->createLink('testcase', 'browse', "productID=$productID&browseType=byModule&param=$moduleID"), 'parent'));
        }
        if(empty($this->products)) $this->locate($this->createLink('product', 'create'));

        /* Set productID and currentModuleID. */
        $productID       = $this->product->saveState($productID, $this->products);
        $currentModuleID = (int)$moduleID;

        /* Set menu. */
        $this->testcase->setMenu($this->products, $productID);

        /* Init vars. */
        $type         = 'feature';
        $title        = '';

        $title      = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->batchCreate;
        $position[] = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
        $position[] = $this->lang->testcase->batchCreate;

        $users = $this->user->getPairs();
        $this->view->title            = $title;
        $this->view->position         = $position;
        $this->view->productID        = $productID;
        $this->view->users            = $users;           
        $this->view->productName      = $this->products[$productID];
        $this->view->moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0);
        $this->view->currentModuleID  = $currentModuleID;
        $this->view->stories          = $this->story->getProductStoryPairs($productID);
        $this->view->type             = $type;
        $this->view->title            = $title;

        $this->display();
    }

    /**
     * View a test case.
     * 
     * @param  int   $caseID 
     * @param  int   $version 
     * @access public
     * @return void
     */
    public function view($caseID, $version = 0)
    {
        $case = $this->testcase->getById($caseID, $version);
        if(!$case) die(js::error($this->lang->notFound) . js::locate('back'));

        $productID = $case->product;
        $this->testcase->setMenu($this->products, $productID);

        $this->view->title      = "CASE #$case->id $case->title - " . $this->products[$productID];
        $this->view->position[] = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
        $this->view->position[] = $this->lang->testcase->view;

        $this->view->case           = $case;
        $this->view->productName    = $this->products[$productID];
        $this->view->modulePath     = $this->tree->getParents($case->module);
        $this->view->users          = $this->user->getPairs('noletter');
        $this->view->actions        = $this->loadModel('action')->getList('case', $caseID);
        $this->view->preAndNext     = $this->loadModel('common')->getPreAndNextObject('testcase', $caseID);

        $this->display();
    }

    /**
     * Edit a case.
     * 
     * @param  int   $caseID 
     * @access public
     * @return void
     */
    public function edit($caseID, $comment = false)
    {
        $this->loadModel('story');

        if(!empty($_POST))
        {
            $changes = array();
            $files   = array();
            if($comment == false)
            {
                $changes = $this->testcase->update($caseID);
                if(dao::isError()) die(js::error(dao::getError()));
                $files = $this->loadModel('file')->saveUpload('testcase', $caseID);
            }
            if($this->post->comment != '' or !empty($changes) or !empty($files))
            {
                $this->loadModel('action');
                $action = !empty($changes) ? 'Edited' : 'Commented';
                $fileAction = '';
                if(!empty($files)) $fileAction = $this->lang->addFiles . join(',', $files) . "\n";
                $actionID = $this->action->create('case', $caseID, $action, $fileAction . $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }
            die(js::locate($this->createLink('testcase', 'view', "caseID=$caseID"), 'parent'));
        }

        $case = $this->testcase->getById($caseID);
        if(empty($case->steps))
        {
            $step = new stdclass();
            $step->desc   = '';
            $step->expect = '';
            $case->steps[] = $step;
        }
        $productID       = $case->product;
        $currentModuleID = $case->module;
        $title           = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->edit;
        $position[]      = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
        $position[]      = $this->lang->testcase->edit;

        /* Set menu. */
        $this->testcase->setMenu($this->products, $productID);

        $users = $this->user->getPairs();
        $this->view->title            = $title;
        $this->view->position         = $position;
        $this->view->productID        = $productID;
        $this->view->productName      = $this->products[$productID];
        $this->view->moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0);
        $this->view->currentModuleID  = $currentModuleID;
        $this->view->users            = $users;
        $this->view->stories          = $this->story->getProductStoryPairs($productID);
        $this->view->case             = $case;
        $this->view->actions          = $this->loadModel('action')->getList('case', $caseID);

        $this->display();
    }

    /**
     * Batch edit case.
     * 
     * @param  string $from example:testcaseBrowse,testtaskCases,testcaseBatchEdit
     * @param  int    $productID 
     * @param  string $orderBy 
     * @access public
     * @return void
     */
    public function batchEdit($from = '', $productID = 0, $orderBy = '')
    {
        if($from == 'testcaseBrowse' or $from == 'testtaskCases')
        {
            /* Init vars. */
            $orderBy         = $orderBy ? $orderBy : 'id_desc';
            $caseIDList      = $this->post->caseIDList ? $this->post->caseIDList : array();
            $product         = $this->product->getByID($productID);
            $editedCases     = array();
            $columns         = 7;
            $showSuhosinInfo = false;

            /* Get all cases. */
            $allCases = $this->dao->select('*')->from(TABLE_CASE)->where('id')->in($caseIDList)->orderBy($orderBy)->fetchAll('id');

            /* Set product menu. */
            $this->testcase->setMenu($this->products, $productID);

            /* Initialize the cases whose need to edited. */
            foreach($allCases as $case) if(in_array($case->id, $caseIDList)) $editedCases[$case->id] = $case;

            /* Judge whether the editedTasks is too large. */
            $showSuhosinInfo = $this->loadModel('common')->judgeSuhosinSetting(count($editedCases), $columns);

            /* Set the sessions. */
            $this->app->session->set('showSuhosinInfo', $showSuhosinInfo);

            /* Assign. */
            $this->view->title      = $product->name . $this->lang->colon . $this->lang->testcase->batchEdit;
            $this->view->position[] = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
            $this->view->position[] = $this->lang->testcase->common;
            $this->view->position[] = $this->lang->testcase->batchEdit;

            if($showSuhosinInfo) $this->view->suhosinInfo = $this->lang->suhosinInfo;
            $this->view->moduleOptionMenu = $this->tree->getOptionMenu($productID, $viewType = 'case', $startModuleID = 0);
            $this->view->productID        = $productID;
            $this->view->editedCases      = $editedCases;
            $this->view->users            = $this->user->getPairs('nodeleted,qafirst');

            $this->display();
        }
        elseif($from == 'testcaseBatchEdit')
        {
            if(!empty($_POST))
            {
                $allChanges = $this->testcase->batchUpdate();
                if($allChanges)
                {
                    foreach($allChanges as $caseID => $changes )
                    {
                        $actionID = $this->loadModel('action')->create('case', $caseID, 'Edited');
                        $this->action->logHistory($actionID, $changes);
                    }
                }
            }
            die(js::locate($this->session->caseList, 'parent'));
        }
    }

    /**
     * Delete a test case
     * 
     * @param  int    $caseID 
     * @param  string $confirm yes|noe
     * @access public
     * @return void
     */
    public function delete($caseID, $confirm = 'no')
    {
        if($confirm == 'no')
        {
            die(js::confirm($this->lang->testcase->confirmDelete, inlink('delete', "caseID=$caseID&confirm=yes")));
        }
        else
        {
            $this->testcase->delete(TABLE_CASE, $caseID);
            die(js::locate($this->session->caseList, 'parent'));
        }
    }

    /**
     * Confirm story changes.
     * 
     * @param  int   $caseID 
     * @access public
     * @return void
     */
    public function confirmStoryChange($caseID)
    {
        $case = $this->testcase->getById($caseID);
        $this->dao->update(TABLE_CASE)->set('storyVersion')->eq($case->latestStoryVersion)->where('id')->eq($caseID)->exec();
        $this->loadModel('action')->create('case', $caseID, 'confirmed', '', $case->latestStoryVersion);
        die(js::reload('parent'));
    }

    /**
     * export 
     * 
     * @param  int    $productID 
     * @param  string $orderBy 
     * @param  int    $taskID 
     * @access public
     * @return void
     */
    public function export($productID, $orderBy, $taskID = 0)
    {
        if($_POST)
        {
            $caseLang   = $this->lang->testcase;
            $caseConfig = $this->config->testcase;

            /* Create field lists. */
            $fields = explode(',', $caseConfig->exportFields);
            foreach($fields as $key => $fieldName)
            {
                $fieldName = trim($fieldName);
                $fields[$fieldName] = isset($caseLang->$fieldName) ? $caseLang->$fieldName : $fieldName;
                unset($fields[$key]);
            }

            /* Get cases. */
            if($this->session->testcaseOnlyCondition)
            {
                if($taskID)
                {
                    $caseIDList = $this->dao->select('`case`')->from(TABLE_TESTRUN)->where('task')->eq($taskID)->fetchPairs();
                    $cases = $this->dao->select('*')->from(TABLE_CASE)->where($this->session->testcaseQueryCondition)->andWhere('id')->in($caseIDList)
                        ->beginIF($this->post->exportType == 'selected')->andWhere('id')->in($this->cookie->checkedItem)->fi()
                        ->orderBy($orderBy)->fetchAll('id');
                }
                else
                {
                    $cases = $this->dao->select('*')->from(TABLE_CASE)->where($this->session->testcaseQueryCondition)
                        ->beginIF($this->post->exportType == 'selected')->andWhere('id')->in($this->cookie->checkedItem)->fi()
                        ->orderBy($orderBy)->fetchAll('id');
                }
            }
            else
            {
                $cases   = array();
                $orderBy = " ORDER BY " . str_replace(array('|', '^A', '_'), ' ', $orderBy);
                $stmt    = $this->dbh->query($this->session->testcaseQueryCondition . $orderBy);
                while($row = $stmt->fetch())
                {
                    $caseID = isset($row->case) ? $row->case : $row->id;
                    if($this->post->exportType == 'selected' and strpos(",{$this->cookie->checkedItem},", ",$caseID,") === false) continue;
                    $cases[$caseID] = $row;
                    $row->id        = $caseID;
                }
            }

            /* Get users, products and projects. */
            $users    = $this->loadModel('user')->getPairs('noletter');
            $products = $this->loadModel('product')->getPairs();

            /* Get related objects id lists. */
            $relatedModuleIdList = array();
            $relatedStoryIdList  = array();
            $relatedCaseIdList   = array();

            foreach($cases as $case)
            {
                $relatedModuleIdList[$case->module] = $case->module;
                $relatedStoryIdList[$case->story]   = $case->story;
                $relatedCaseIdList[$case->linkCase] = $case->linkCase;

                /* Process link cases. */
                $linkCases = explode(',', $case->linkCase);
                foreach($linkCases as $linkCaseID)
                {
                    if($linkCaseID) $relatedCaseIdList[$linkCaseID] = trim($linkCaseID);
                }
            }

            /* Get related objects title or names. */
            $relatedModules = $this->dao->select('id, name')->from(TABLE_MODULE)->where('id')->in($relatedModuleIdList)->fetchPairs();
            $relatedStories = $this->dao->select('id,title')->from(TABLE_STORY) ->where('id')->in($relatedStoryIdList)->fetchPairs();
            $relatedCases   = $this->dao->select('id, title')->from(TABLE_CASE)->where('id')->in($relatedCaseIdList)->fetchPairs();
            $relatedSteps   = $this->dao->select('`case`, version, `desc`, expect')->from(TABLE_CASESTEP)->where('`case`')->in(@array_keys($cases))->orderBy('version desc,id')->fetchGroup('case');
            $relatedModules = array('0' => '/') + $relatedModules;

            foreach($cases as $case)
            {
                $case->stepDesc   = '';
                $case->stepExpect = '';
                if(isset($relatedSteps[$case->id]))
                {
                    $i = 1;
                    foreach($relatedSteps[$case->id] as $step)
                    {
                        if($step->version != $case->version) continue;
                        $sign = (in_array($this->post->fileType, array('html', 'xml'))) ? '<br />' : "\n";
                        $case->stepDesc   .= $i . ". " . $step->desc . $sign;
                        $case->stepExpect .= $i . ". " . $step->expect . $sign;
                        $i ++;
                    }
                }

                if($this->post->fileType == 'csv')
                {
                    $case->stepDesc   = str_replace('"', '""', $case->stepDesc);
                    $case->stepExpect = str_replace('"', '""', $case->stepExpect);
                }

                /* fill some field with useful value. */
                if(isset($products[$case->product]))      $case->product = $products[$case->product];
                if(isset($relatedModules[$case->module])) $case->module  = $relatedModules[$case->module];
                if(isset($relatedStories[$case->story]))  $case->story   = $relatedStories[$case->story];

                if(isset($caseLang->priList[$case->pri]))       $case->pri          = $caseLang->priList[$case->pri];
                if(isset($caseLang->typeList[$case->type]))     $case->type         = $caseLang->typeList[$case->type];
                if(isset($caseLang->stageList[$case->stage]))   $case->stage        = $caseLang->stageList[$case->stage];
                if(isset($caseLang->statusList[$case->status])) $case->status       = $caseLang->statusList[$case->status];
                if(isset($users[$case->openedBy]))              $case->openedBy     = $users[$case->openedBy];
                if(isset($users[$case->lastEditedBy]))          $case->lastEditedBy = $users[$case->lastEditedBy];

                $case->openedDate     = substr($case->openedDate, 0, 10);
                $case->lastEditedDate = substr($case->lastEditedDate, 0, 10);

                if($case->linkCase)
                {
                    $tmpLinkCases = array();
                    $linkCaseIdList = explode(',', $case->linkCase);
                    foreach($linkCaseIdList as $linkCaseID)
                    {
                        $linkCaseID = trim($linkCaseID);
                        $tmpLinkCases[] = isset($relatedCases[$linkCaseID]) ? $relatedCases[$linkCaseID] : $linkCaseID;
                    }
                    $case->linkCase = join("; \n", $tmpLinkCases);
                }
            }

            $this->post->set('fields', $fields);
            $this->post->set('rows', $cases);
            $this->post->set('kind', 'testcase');
            $this->fetch('file', 'export2' . $this->post->fileType, $_POST);
        }

        $this->display();
    }
}
