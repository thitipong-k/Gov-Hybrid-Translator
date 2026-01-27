<?php
namespace GovHybridTranslator\Admin;

use GovHybridTranslator\Admin\Ajax\GlossaryAjax;
use GovHybridTranslator\Admin\Ajax\ContentReviewAjax;
use GovHybridTranslator\Admin\Ajax\TranslationAjax;
use GovHybridTranslator\Admin\Ajax\SettingsAjax;
use GovHybridTranslator\Admin\Ajax\DesignTabsAjax;

class Ajax {

    public function register() {
        (new GlossaryAjax())->register();
        (new ContentReviewAjax())->register();
        (new TranslationAjax())->register();
        (new SettingsAjax())->register();
        (new DesignTabsAjax())->register();
    }
}
