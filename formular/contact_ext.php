<?php if(!defined('IS_CMS')) die(); ?>
<div class="fm-box-type">
    <p class="fm-success-nodisplay">{MESSAGES_SUCCESS}</p>
    <p class="fm-mandatory-nodisplay fm-mailvalid-nodisplay fm-mailcopy-nodisplay fm-spamtask-nodisplay">Beachten sie bitte die Hinweise</p>
    <p class="fm-senttoofast-nodisplay">{MESSAGES_SENTTOOFAST}</p>
</div>
<!-- um auch hier die styles vom contact.php zu nutzen -->
<div class="fm-name-contact fm-ext">
<div class="td-div-col2 fm-mo-elm">
    <span class="fm-noms fm-ms-fm-01-ma">{USER_TITLE} ist ein Pflichtfeld</span>
    <select name="ASTALAVISTA_01" size="1" class="fm-du-elm-fm-01-ma">
        <option>{USER_TITLE}</option>
        <option value="user_frau">{USER_FRAU}</option>
        <option value="user_herr">{USER_HERR}</option>
    </select><br />
</div>
<div class="table-div">
    <div class="tr-div">
        <div class="td-div td-left"><label for="contact_02">{USER_NAME}</label> *</div>
        <div class="td-div">
            <span class="fm-noms fm-ms-fm-02-ma">{USER_NAME} ist ein Pflichtfeld</span>
            <input style="test" type="text" id="contact_02" name="contact_02" class="fm-du-elm-fm-02-ma" value="" /></div>
    </div>
    <div class="tr-div fm-mo-elm">
        <div class="td-div td-left"><label for="contact_03">{USER_PHONE}</label> *</div>
        <div class="td-div">
            <span class="fm-noms fm-ms-fm-03-ma">{USER_PHONE} ist ein Pflichtfeld</span>
            <input id="contact_03" type="text" name="ASTALAVISTA_02" class="fm-du-elm-fm-03-ma" value="" /></div>
    </div>
    <div class="tr-div">
        <div class="td-div td-left"><label for="contact_04">{USER_WEBSITE}</label></div>
        <div class="td-div"><input type="text" id="contact_04" name="contact_04" value="" /></div>
    </div>
    <div class="tr-div">
        <div class="td-div td-left"><label for="contact_05">{USER_MAIL}</label></div>
        <div class="td-div">
            <span class="fm-noms fm-ms-mailnovalid">Die E-Mail ist nicht korrekt</span>
            <span class="fm-noms fm-mailcopy-nodisplay">Für eine Kopie wird eine E-Mail Benötigt</span>
            <input class="fm-ma-elm-mailnovalid" type="text" id="contact_05" name="type_mail" value="" /></div>
    </div>
    <div class="tr-div">
        <div class="td-div td-left td-top"><label for="contact_06">{USER_MESSAGE}</label> *</div>
        <div class="td-div">
            <span class="fm-noms fm-ms-fm-06-ma">{USER_MESSAGE} ist ein Pflichtfeld</span>
            <textarea rows="10" cols="50" id="contact_06" name="contact_06" class="fm-du-elm-fm-06-ma"></textarea></div>
    </div>
</div>
<div class="td-div-col2">{SPAMPROTECTION}</div>
<div class="table-div">
    <div class="tr-div">
        <div class="td-div td-left td-right"><label for="contact_07">{SPAM_TASK}</label></div>
        <div class="td-div">
            <span class="fm-noms fm-spamtask-nodisplay">Die Antwort ist Falsch</span>
            <input id="contact_07" type="text" name="SPAM_TASK" value="" class="fm-du-elm-fm-07-ma" />
        </div>
    </div>
</div>
<div class="td-div-col2">
    <span class="fm-noms fm-ms-fm-08-ma">Dem Datenschutz muss zugestimmt werden</span>
    <input type="checkbox" id="contact_08" name="contact_08" value="privacy" class="fm-cursor fm-du--fm-08-ma" />* <label for="contact_08" class="fm-cursor">{PRIVACY}</label></div>
<div class="td-div-col2"><input id="contact_09" type="checkbox" name="type_mail_cc" value="true" class="fm-cursor" /><label for="contact_09" class="fm-cursor">{MAILCOPY}</label></div>
<div class="table-div">
    <div class="tr-div">
        <div class="td-div">{MANDATORY}</div>
        <div class="td-div td-center"><input type="submit" class="submit" name="SUBMIT" value="{SUBMIT}" /></div>
    </div>
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