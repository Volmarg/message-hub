<template>
  <Base :content="content"
        hover-text="Retry"
        :dialog-size="dialogSize"
        extra-classes="fa fa-redo"
        :show-dialog-cancel-button="showDialogCancelButton"
        :show-dialog-confirmation-button="showDialogConfirmationButton"
        :dialog-cancel-button-translation-string="dialogCancelButtonTranslationString"
        @material-modal-confirm-button-click="$emit('resendClicked', this.entityId)"
  >
    <IframeWithContent :content="content"
                       v-if="useIframe"
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

export default {
  props: {
    entityId: {
      type:     [Number],
      required: true
    },
    dialogCancelButtonTranslationString: {
      type:     [String],
      required: false,
    },
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
  emits: [
    'resendClicked'
  ],
  components: {
    Base,
    Dialog,
    IframeWithContent
  },
}
</script>
