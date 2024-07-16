<template>
  <div class="iframe-wrapper">
    <iframe ref="iframe"
            class="iframe"
    ></iframe>
  </div>
</template>

<script>
/**
 * There is known issue with setting iframe 100% height etc.
 * - non of the solutions worked,
 * - the `document.offsetHeight` etc. are returning `0` even with the `iframe.onload` event,
 */
export default {
  props: {
    content: {
      type:     [String],
      required: true
    }
  },
  mounted() {
    let iframeDocument = this.$refs.iframe.contentWindow.document;
    iframeDocument.open();
    iframeDocument.write(this.content);
    iframeDocument.close();
  },
}
</script>

<style scoped>
.iframe-wrapper {
  display: flex;
  justify-content: center;
  width: 100%;
  height: 100%;
  min-height: 100%;
  min-width: 100%;
}

.iframe {
  width: 100%;
  height: 100%;
  min-width: 300px !important;
  min-height: 700px !important;
}
</style>