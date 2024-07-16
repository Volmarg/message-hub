<template>
  <i class="option"
     :class="extraClasses"
     :data-tippy-content="hoverText"
     @click="showDialog()"
     ref="icon"
  ></i>

  <Dialog ref="previewDialog"
          :show-cancel-button="showDialogCancelButton"
          :show-confirmation-button="showDialogConfirmationButton"
          :cancel-button-translation-string="dialogCancelButtonTranslationString"
          :dialog-size="dialogSize"
          :min-height="minHeight"
          :min-width="minWidth"
          @material-modal-confirm-button-click="$emit('materialModalConfirmButtonClick')"
  >
    <slot></slot>
  </Dialog>
</template>

<script>
import Dialog from '../../../dialog-modal/material/dialog';

import Tippy from '../../../../libs/tippy/Tippy';

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
    extraClasses: {
      type:     [String],
      required: false
    },
    hoverText: {
      type:     [String],
      required: false,
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
  },
  components: {
    Dialog
  },
  computed: {
    minHeight() {
      return (this.dialogSize === "full" ? "90%" : "auto");
    },
    minWidth() {
      return (this.dialogSize === "full" ? "90%" : "auto");
    }
  },
  methods: {
    showDialog(){
      this.$refs.previewDialog.showDialog();
    }
  },
  mounted(){
    let tippy = new Tippy();
    tippy.applyForElement(this.$refs.icon);
  }
}
</script>

<style lang="scss" scoped>
.option {
  font-size: 20px;
  margin-left: 7px;

  &:hover {
    opacity: 0.7;
    cursor: pointer;
  }
}

</style>