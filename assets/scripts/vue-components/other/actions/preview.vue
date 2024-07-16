<template>
  <Base :content="content"
        :subject="subject"
        hover-text="Preview"
        :dialog-size="dialogSize"
        extra-classes="fas fa-eye"
        :show-dialog-cancel-button="showDialogCancelButton"
        :show-dialog-confirmation-button="showDialogConfirmationButton"
        dialog-cancel-button-translation-string="mainPageComponents.dialog.buttons.main.close.label"
  >
    <p><b>{{subjectTranslation}}:</b> {{ subject }}</p>
    <p><b>{{bodyTranslation}}:</b></p>
    <IframeWithContent :content="content"
                       v-if="useIframe && iframeReady"
    />

    <span v-html="content"
          v-else
    />
  </Base>
</template>

<script>
import Base              from "./components/base";
import Dialog            from '../../dialog-modal/material/dialog';
import IframeWithContent from '../iframe-with-content';

import TranslationsService from '../../../core/services/TranslationsService';

export default {
  data() {
    return {
      iframeReady: true,
    }
  },
  props: {
    showDialogCancelButton: {
      type:     [Boolean],
      required: false,
      default:  true
    },
    showDialogConfirmationButton: {
      type:     [Boolean],
      required: false,
      default:  true
    },
    content: {
      type:     [String],
      required: true
    },
    subject: {
      type:     [String],
      required: true
    },
    dialogSize: {
      type:     [String],
      required: false,
      default:  'standard'
    },
    useIframe: {
      type:     [Boolean],
      required: false,
      default:  false
    }
  },
  components: {
    Base,
    Dialog,
    IframeWithContent
  },
  computed: {
    /**
     * @description provides body string translation for dialog
     */
    bodyTranslation() {
      return (new TranslationsService()).getTranslationForString('pages.mailing.history.preview.dialog.body');
    },
    /**
     * @description provides subject string translation for dialog
     */
    subjectTranslation() {
      return (new TranslationsService()).getTranslationForString('pages.mailing.history.preview.dialog.subject');
    },
  },
  watch: {
    /**
     * @description this is a fix for the iframe not reloading content properly
     */
    content() {
      this.iframeReady = false;
      this.$nextTick(() => {
        this.iframeReady = true;
      })
    }
  }
}
</script>
