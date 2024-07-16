<template>
  <Base :content="content"
        hover-text="Send to"
        :dialog-size="dialogSize"
        extra-classes="fa fa-envelope"
        :show-dialog-cancel-button="showDialogCancelButton"
        :show-dialog-confirmation-button="showDialogConfirmationButton"
        :dialog-cancel-button-translation-string="dialogCancelButtonTranslationString"
        @material-modal-confirm-button-click="sendToClicked"
  >
    <IframeWithContent :content="content"
                       v-if="useIframe"
    />

    <div v-if="isContentComponent">
      <component :is="content.component"
                 v-bind="content.props"
                 ref="sendToDialogContentComponent"
      />
    </div>

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
      type:     [String, Object],
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
  computed: {
    isContentComponent() {
      return (
            "object" === typeof this.content
          && this.content.component
      );
    }
  },
  emits: [
    'sendToClicked'
  ],
  components: {
    Base,
    Dialog,
    IframeWithContent
  },
  methods: {
    /**
     * Handler for when user clicks confirmation button in the `send to` dialog
     */
    sendToClicked() {
      let data = this.$refs.sendToDialogContentComponent.getData();
      this.$emit('sendToClicked', data);
    }
  }
}
</script>
