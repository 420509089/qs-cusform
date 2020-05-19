<?php


namespace CusForm\Model;


use Gy_Library\DBCont;
use Gy_Library\GyListModel;
use Think\Exception;

class FormApplyContentModel extends GyListModel
{
    protected $_validate=[
        ['form_apply_id','require','缺少form_apply_id'],
        ['form_item_id','require','缺少form_item_id'],
    ];

    protected $_auto = array(
        array('create_date', "time", parent::MODEL_INSERT, 'function'),
    );

    public function saveAll($form_id, $data){
        $formItemModel=new FormItemModel();
        $formItems=$formItemModel->where(['form_id'=>$form_id])->order('sort')->select();
        if (!$formItems){
            return true;
        }
        $this->startTrans();
        try{
            C('TOKEN_ON',false);
            $formApplyModel=new FormApplyModel();
            $apply_id=$formApplyModel->createAdd(['create_date'=>time()]);
            if ($apply_id===false){
                E($formApplyModel->getError());
            }
            dump($apply_id);

            foreach ($formItems as $formItem) {
                if (!isset($data['cus_form_'.$formItem['id']])){
                    $data['cus_form_'.$formItem['id']]='';
                }
                if ($formItem['type']==FormItemModel::CHECK_BOX){
                    $data['cus_form_'.$formItem['id']]=implode(',',$data['cus_form_'.$formItem['id']]);
                }
                if ($formItem['required']==DBCont::NO_BOOL_STATUS
                    || ($formItem['required']==DBCont::YES_BOOL_STATUS
                        && trim($data['cus_form_'.$formItem['id']]))
                ){
                    $r=$formItemModel->checkLimit($data['cus_form_'.$formItem['id']],$formItem);
                    if ($r===false){
                        E($formItemModel->getError());
                    }

                    $content=[
                        'form_apply_id'=>$apply_id,
                        'form_item_id'=>$formItem['id'],
                        'content'=>$data['cus_form_'.$formItem['id']],
                    ];
                    if ($this->createAdd($content)===false){
                        E($this->getError());
                    }
                }else{
                    E('缺少'.$formItem['title']);
                }
            }
            $this->commit();
            return $apply_id;
        }catch (Exception $e){
            $this->rollback();
            $this->error=$e->getMessage();
            return false;
        }
    }
}