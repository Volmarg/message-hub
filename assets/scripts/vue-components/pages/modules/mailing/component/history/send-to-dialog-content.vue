<template>
  <InputField type="email"
              label="E-mail address"
              ref="emailAddress"
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
        emailAddress : this.$refs.emailAddress.textFieldValue,
        id           : this.entityId,
      }
    }
  }

}
</script>