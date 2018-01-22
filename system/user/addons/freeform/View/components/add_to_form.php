<?php if (empty($available_forms)):?>
    <?=lang('no_forms')?>
<?php else:?>
    <div id="form_list" class="field_list w-8">
        <?php foreach($available_forms as $form_name => $form_data):?>
            <div class="field_tag" data-form-id="<?=$form_data['form_id']?>">
                <?=$form_data['form_label']?>
            </div>
        <?php endforeach;?>
    </div>
    <div id="chosen_form_list" class="chosen_field_list w-8"></div>
<?php endif;?>
