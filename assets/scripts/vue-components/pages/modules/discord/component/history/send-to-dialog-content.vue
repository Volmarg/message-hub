<template>
  <InputField type="text"
              label="Discord webhook"
              ref="webhook"
  />
</template>

<script>
import InputField from '../../../../../form/components/material/input-field';

export default {
  components: {
    InputField,
  },
  props: {
    entityId: {
      type:     [Number],
      required: true
    }
  },
  methods: {
    /**
     * @description return form data
     */
    getData() {
      return {
        webhookUrl : this.$refs.webhook.textFieldValue,
        id         : this.entityId,
      }
    }
  }

}
</script>