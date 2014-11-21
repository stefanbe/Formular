<?php if(!defined('IS_CMS')) die(); ?>
<div class="fm-box-type">
    <p class="fm-mandatory-nodisplay">{MESSAGES_MANDATORY}</p>
    <p class="fm-success-nodisplay">{MESSAGES_SUCCESS}</p>
    <p class="fm-mailvalid-nodisplay">{MESSAGES_MAILVALID}</p>
    <p class="fm-senttoofast-nodisplay">{MESSAGES_SENTTOOFAST}</p>
    <p class="fm-mailcopy-nodisplay">{MESSAGES_MAILCOPY}</p>
    <p class="fm-spamtask-nodisplay">{MESSAGES_SPAMTASK}</p>
</div>
<div class="td-div-col2 fm-mo-elm">
    <select name="ASTALAVISTA_01" size="1" class="fm-ma-elm-fm-01-ma">
        <option>{USER_TITLE}</option>
        <option value="user_frau">{USER_FRAU}</option>
        <option value="user_herr">{USER_HERR}</option>
    </select><br />
</div>
<div class="table-div">
    <div class="tr-div">
        <div class="td-div td-left"><label for="contact_02" class="fm-ma-elm-fm-02-ma">{USER_NAME}</label> *</div>
        <div class="td-div"><input style="test" type="text" id="contact_02" name="contact_02" class="fm-ma-elm-fm-02-ma" value="" /></div>
    </div>
    <div class="tr-div fm-mo-elm">
        <div class="td-div td-left"><label for="contact_03" class="fm-ma-elm-fm-03-ma">{USER_PHONE}</label> *</div>
        <div class="td-div"><input id="contact_03" type="text" name="ASTALAVISTA_02" class="fm-ma-elm-fm-03-ma" value="" /></div>
    </div>
    <div class="tr-div">
        <div class="td-div td-left"><label for="contact_04">{USER_WEBSITE}</label></div>
        <div class="td-div"><input type="text" id="contact_04" name="contact_04" value="" /></div>
    </div>
    <div class="tr-div">
        <div class="td-div td-left"><label for="contact_05" class="fm-ma-elm-mailnovalid fm-ma-elm-fm-05-ma">{USER_MAIL}</label> *</div>
        <div class="td-div"><input class="fm-ma-elm-mailnovalid fm-ma-elm-fm-05-ma" type="text" id="contact_05" name="type_mail" value="" /></div>
    </div>
    <div class="tr-div">
        <div class="td-div td-left td-top"><label for="contact_06" class="fm-ma-elm-fm-06-ma">{USER_MESSAGE}</label> *</div>
        <div class="td-div"><textarea rows="10" cols="50" id="contact_06" name="contact_06" class="fm-ma-elm-fm-06-ma"></textarea></div>
    </div>
</div>
<div class="td-div-col2">{SPAMPROTECTION}</div>
<div class="table-div">
    <div class="tr-div">
        <div class="td-div td-left td-right"><label for="contact_07" class="fm-ma-elm-fm-07-ma">{SPAM_TASK}</label></div>
        <div class="td-div">
            <input id="contact_07" type="text" name="SPAM_TASK" value="" class="fm-ma-elm-fm-07-ma" />
        </div>
    </div>
</div>
<div class="td-div-col2"><input type="checkbox" id="contact_08" name="contact_08" value="privacy" class="fm-cursor dummy-fm-08-ma" />* <label for="contact_08" class="fm-cursor fm-ma-elm-fm-08-ma">{PRIVACY}</label></div>
<div class="td-div-col2"><input id="contact_09" type="checkbox" name="type_mail_cc" value="true" class="fm-cursor" /><label for="contact_09" class="fm-cursor">{MAILCOPY}</label></div>
<div class="table-div">
    <div class="tr-div">
        <div class="td-div td-left">{MANDATORY}</div>
        <div class="td-div td-center"><input type="submit" class="submit" name="SUBMIT" value="{SUBMIT}" /></div>
    </div>
</div>
<!--<mail_from></mail_from>-->
<!--<mail_to></mail_to>-->
<mail_subject>{USER_SUBJECT}</mail_subject>
<mail_txt>
{USER_HEAD}

{MAIL_TEXT}
{USER_MFG} Der Webseiten Betreiber
</mail_txt>