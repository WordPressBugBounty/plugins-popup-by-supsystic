<?php

if (!defined('ABSPATH')) {
  exit();
}

class CfImportSubmoduleViewPpsgen extends BaseViewPpsgen
{
  public function renderImportPage($params)
  {
    $model = $this->getModel();
    $request = $this->getFrame()->core('request');
    $scan = $model->scan();
    $preselect = array_values(array_filter(array_map('intval', explode(',', (string) $request->getVar('ids', 'get')))));
    $this->assign([
      'scan' => $scan,
      'fromCf' => sanitize_key((string) $request->getVar('from', 'get')) === 'cfs',
      'preselect' => $preselect,
      'formsUrl' => admin_url('admin.php?page=ppsgen&tab=form_list'),
      'templatesUrl' => admin_url('admin.php?page=ppsgen&tab=template_add&template_type=form'),
    ]);
    // The list is drawn by the script from this data (the same data the AJAX calls return)
    wp_add_inline_script('ppsgen-backend-cf-import', 'window.ppsgenCfImportData = ' . wp_json_encode(['scan' => $scan, 'preselect' => $preselect], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . ';', 'before');
    return $this->renderTpl('import', 'form', 'cf_import');
  }

  public function renderBanner(int $count): string
  {
    $this->assign(['count' => $count, 'url' => CfImportSubmodulePpsgen::getPageUrl()]);
    return (string) $this->renderTpl('banner', 'form', 'cf_import');
  }

  public function getJsStrings(): array
  {
    return [
      'moveAll' => __('Move all forms', 'ppsgen'),
      'moveAllN' => __('Move all %d forms', 'ppsgen'),
      'moveSelected' => __('Move selected (%d)', 'ppsgen'),
      'moving' => __('Moving your forms…', 'ppsgen'),
      'moved' => __('Moved', 'ppsgen'),
      'ready' => __('Ready to move', 'ppsgen'),
      'inactive' => __('Off in Contact Form', 'ppsgen'),
      'openInCes' => __('Open in CES', 'ppsgen'),
      'cesForm' => __('CES form #%d', 'ppsgen'),
      'fields' => __('%d fields', 'ppsgen'),
      'oneField' => __('1 field', 'ppsgen'),
      'submissions' => __('%d submissions', 'ppsgen'),
      'oneSubmission' => __('1 submission', 'ppsgen'),
      'noSubmissions' => __('No submissions', 'ppsgen'),
      'newSubmissions' => __('%d new since the move', 'ppsgen'),
      'pages' => __('On %d pages', 'ppsgen'),
      'onePage' => __('On 1 page', 'ppsgen'),
      'notOnPages' => __('Not on any page', 'ppsgen'),
      'template' => __('Template: %s', 'ppsgen'),
      'proUsed' => __('Uses: %s', 'ppsgen'),
      'empty' => __('Contact Form has no forms on this site. Forms you make in Contact Form later can be moved here at any time.', 'ppsgen'),
      'noSource' => __('Contact Form by Supsystic data was not found on this site.', 'ppsgen'),
      'resultTitle' => __('Done: %1$d moved, %2$d already in CES', 'ppsgen'),
      'resultLead' => __('Each form below works in CES now, with the standard CES look. Choose a template in the form editor, then replace the shortcodes on your pages.', 'ppsgen'),
      'chooseTemplate' => __('Choose a template', 'ppsgen'),
      'movedList' => __('Moved', 'ppsgen'),
      'skippedList' => __('Not moved', 'ppsgen'),
      'skippedNone' => __('Everything of this form was moved.', 'ppsgen'),
      'alreadyMoved' => __('Already in CES, nothing was copied twice.', 'ppsgen'),
      'subsMoved' => __('Submissions moved: %1$d. New contacts: %2$d. Linked to existing contacts: %3$d.', 'ppsgen'),
      'subsNone' => __('No new submissions to move.', 'ppsgen'),
      'error' => __('Something went wrong. Nothing was changed in Contact Form. Please try again.', 'ppsgen'),
      'scError' => __('The shortcodes could not be changed. Please try again.', 'ppsgen'),
      'scNone' => __('None of your pages show a Contact Form shortcode.', 'ppsgen'),
      'scWaiting' => __('Not moved yet:', 'ppsgen'),
      'scReplace' => __('Replace on %d places', 'ppsgen'),
      'scReplaceOne' => __('Replace on 1 place', 'ppsgen'),
      'scReplaced' => __('Done: %d shortcodes replaced. The old text of each page is kept, so you can undo it.', 'ppsgen'),
      'scUndo' => __('Undo the replacement', 'ppsgen'),
      'scUndone' => __('The old shortcodes are back on %d places.', 'ppsgen'),
      'scDoneTitle' => __('Replaced', 'ppsgen'),
      'scDoneCount' => __('Replaced: %d', 'ppsgen'),
      'scColumn' => __('Shortcode now → after the replacement', 'ppsgen'),
      'scColumnDone' => __('Replacement', 'ppsgen'),
      'scConfirm' => __('Replace the Contact Form shortcodes with the CES ones on these pages? You can undo it here.', 'ppsgen'),
      'scUndoConfirm' => __('Put the old Contact Form shortcodes back?', 'ppsgen'),
      'edit' => __('Page', 'ppsgen'),
      'widget' => __('Widget', 'ppsgen'),
      'abVariants' => __('%d A/B test variants are not moved: CES runs one form, test your versions as separate forms.', 'ppsgen'),
      'abVariantsOne' => __('1 A/B test variant is not moved: CES runs one form, test your versions as separate forms.', 'ppsgen'),
      'popups' => __('%d Classic PopUp popups show a Contact Form form inside. They keep working while Contact Form is active; in CES, add the moved form to a new popup.', 'ppsgen'),
      'popupsOne' => __('1 Classic PopUp popup shows a Contact Form form inside. It keeps working while Contact Form is active; in CES, add the moved form to a new popup.', 'ppsgen'),
      'widgets' => __('%d Contact Form widgets: replace each with a Shortcode widget that shows the CES shortcode.', 'ppsgen'),
      'widgetsOne' => __('1 Contact Form widget: replace it with a Shortcode widget that shows the CES shortcode.', 'ppsgen'),
      'limit' => __('CES Free adds up to %1$d contacts in bulk moves (you have %2$d). Submissions are always moved.', 'ppsgen'),
      /* translators: 1: coupon code, 2: discount in percent */
      'couponTitle' => __('Coupon %1$s: −%2$d%% on CES PRO', 'ppsgen'),
      'couponTextPro' => __('Some features of your forms are in CES PRO: file uploads, field icons and the other PRO field types. The button puts CES PRO in the cart with your coupon.', 'ppsgen'),
      'couponTextFree' => __('CES PRO adds file uploads, more field types, 16 more design styles and no limit on contacts. The button puts CES PRO in the cart with your coupon.', 'ppsgen'),
      'couponBtn' => __('Get CES PRO', 'ppsgen'),
      'selectAll' => __('Select all', 'ppsgen'),
      'copy' => __('Copy', 'ppsgen'),
      'copied' => __('Copied', 'ppsgen'),
    ];
  }
}
