<template>
  <div class="additional-information-wrapper">
    <h3 class="additional-information-title"><i class="sas-icon sas-icon-pencil" aria-hidden="true" /> Informations complémentaires</h3>
    <p class="additional-information-text">{{ additionalInfo }}</p>
  </div>
</template>
<script>
export default {
    props: {
        additionalInfo: {
            type: String,
            default: '',
        },
    },
};
</script>
