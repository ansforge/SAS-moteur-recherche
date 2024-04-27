<template>
  <VeeForm
    v-if="isLoaded"
    :validation-schema="schema"
    as="form"
    enctype="multipart/form-data"
    @invalid-submit="onInvalidSubmit"
    @submit="uploadForm"
  >
    <div class="sas-faq">
      <h1 class="faq-title">Formulaire de contact</h1>

      <Notification
        v-if="displayNotification"
        :status="notificationStatus"
        :message="notificationMsg"
      />

      <div class="form-item">
        <label class="form-item-label" for="role">Vous êtes<sup class="txt-red">*</sup></label>
        <div class="form-item-group">
          <Field
            name="role"
            id="role"
            v-slot="{ meta, field }"
            :value="defaultValue.role"
            label="rôle"
          >
            <select
              id="role"
              :class="{ 'sas-form-error': meta.touched && !meta.valid }"
              v-bind="field"
              :disabled="(isConnected || isPscUser()) && defaultValue.role !== 'anonymous'"
              aria-describedby="role-error"
              required
            >
              <option value="">Sélectionner votre rôle utilisateur</option>
              <option
                v-for="(role) in roles"
                v-bind:key="role.label"
                :value="role.label"
              >
                {{ role.label }}
              </option>
            </select>
          </Field>

          <ErrorMessage name="role">
            <p id="role-error" class="txt-red">{{ errorsNotification.role }}</p>
          </ErrorMessage>
        </div>
      </div>

      <div class="form-item">
        <label class="form-item-label" for="topic">Votre demande<sup class="txt-red">*</sup><br> concerne</label>
        <div class="form-item-group">
          <Field
            name="topic"
            id="topic"
            v-slot="{ meta, field }"
            label="thème"
          >
            <select
              id="topic"
              v-bind="field"
              :class="{ 'sas-form-error': meta.touched && !meta.valid }"
              aria-describedby="topic-error"
              required
            >
              <option value="">Choisissez un thème</option>
              <option
                v-for="(theme) in themes"
                v-bind:key="theme.thematic_key"
                :value="theme.thematic_key"
              >
                {{ theme.thematic }}
              </option>
            </select>
          </Field>

          <ErrorMessage name="topic">
            <p id="topic-error" class="txt-red">{{ errorsNotification.topic }}</p>
          </ErrorMessage>
        </div>
      </div>

      <div class="form-item">
        <label class="form-item-label" for="lastname">Nom<sup class="txt-red">*</sup></label>
        <div class="form-item-group">
          <Field
            type="text"
            id="lastName"
            name="lastname"
            v-slot="{ meta, field }"
            :value="defaultValue.lastname"
            label="nom"
          >
            <input
              id="lastName"
              class="form-item-input"
              :class="{ 'sas-form-error': meta.touched && !meta.valid }"
              v-bind="field"
              :disabled="isConnected"
              maxlength="30"
              type="text"
              placeholder="nom"
              autocomplete="family-name"
              aria-describedby="lastname-error"
              required
            >
          </Field>

          <ErrorMessage name="lastname">
            <p id="lastname-error" class="txt-red">{{ errorsNotification.lastname }}</p>
          </ErrorMessage>
        </div>
      </div>

      <div class="form-item">
        <label class="form-item-label" for="firstname">Prénom<sup class="txt-red">*</sup></label>
        <div class="form-item-group">
          <Field
            type="text"
            id="firstname"
            name="firstname"
            v-slot="{ meta, field }"
            :value="defaultValue.firstname"
            label="prénom"
          >
            <input
              id="firstname"
              class="form-item-input"
              :class="{ 'sas-form-error': meta.touched && !meta.valid }"
              v-bind="field"
              :disabled="isConnected"
              maxlength="30"
              type="text"
              placeholder="prénom"
              autocomplete="given-name"
              aria-describedby="firstname-error"
              required
            >
          </Field>

          <ErrorMessage name="firstname">
            <p id="firstname-error" class="txt-red">{{ errorsNotification.firstname }}</p>
          </ErrorMessage>
        </div>
      </div>

      <div class="form-item">
        <label class="form-item-label" for="phone_number">Téléphone<sup class="txt-red">*</sup></label>
        <div class="form-item-group">
          <Field
            type="tel"
            id="phone_number"
            name="phone_number"
            v-slot="{ meta, field }"
            label="numéro de téléphone"
          >
            <input
              id="phone_number"
              class="form-item-input"
              :class="{ 'sas-form-error': meta.touched && !meta.valid }"
              v-bind="field"
              maxlength="10"
              type="tel"
              placeholder="01 22 33 44 55"
              autocomplete="tel"
              aria-describedby="phone-number-error"
              required
            >
          </Field>

          <ErrorMessage name="phone_number">
            <p id="phone-number-error" class="txt-red">{{ errorsNotification.phone_number }}</p>
          </ErrorMessage>
        </div>
      </div>

      <div class="form-item">
        <label class="form-item-label" for="email_address">Adresse E-Mail<sup class="txt-red">*</sup></label>
        <div class="form-item-group">
          <Field
            type="email"
            id="email_address"
            name="email_address"
            placeholder="johndoe@mail.fr"
            v-slot="{ meta, field }"
            :value="defaultValue.email_address"
            lable="email"
          >
            <input
              id="email_address"
              :class="{ 'sas-form-error': meta.touched && !meta.valid }"
              class="form-item-input input-lg"
              v-bind="field"
              :disabled="isConnected"
              maxlength="200"
              placeholder="johndoe@mail.fr"
              type="email"
              autocomplete="email"
              aria-describedby="email-address-error"
              required
            >
          </Field>

          <ErrorMessage name="email_address">
            <p id="email-address-error" class="txt-red">{{ errorsNotification.email_address }}</p>
          </ErrorMessage>
        </div>
      </div>

      <div class="form-item">
        <label class="form-item-label" for="territory">Territoire<sup class="txt-red">*</sup></label>
        <div class="form-item-group">
          <Field
            name="territory"
            id="territory"
            v-slot="{ meta, field }"
            label="territoire"
          >
            <select
              id="territory"
              v-bind="field"
              :class="{ 'sas-form-error': meta.touched && !meta.valid }"
              aria-describedby="territory-error"
              required
            >
              <option value="">Choisissez un territoire ...</option>
              <option
                v-for="(territory) in territories"
                v-bind:key="`territory${territory.id}`"
                :value="territory.name"
              >
                {{ territory.name }}
              </option>
            </select>
          </Field>

          <ErrorMessage name="territory">
            <p id="territory-error" class="txt-red">{{ errorsNotification.territory }}</p>
          </ErrorMessage>
        </div>
      </div>

      <div class="form-item">
        <div class="faq-textarea">
          <label class="form-item-label" for="message">Message<sup class="txt-red">*</sup></label>
          <div class="form-item-group">
            <Field
              id="message"
              name="message"
              v-slot="{ meta, field }"
              label="message"
            >
              <textarea
                id="message"
                :class="{ 'sas-form-error': meta.touched && !meta.valid }"
                v-bind="field"
                maxlength="2000"
                placeholder="Votre message"
                aria-describedby="message-error"
                required
              />
            </Field>

            <ErrorMessage name="message">
              <p id="message-error" class="txt-red">{{ errorsNotification.message }}</p>
            </ErrorMessage>
          </div>
        </div>
      </div>

      <div class="form-item add-files">
        <div class="add-files-content">
          <label class="form-item-label" tabindex="-1">Pièce(s) jointe(s) <br />
            <span class="sas-optionnel">optionnel</span>
          </label>

          <input
            id="ajout"
            ref="inputFile"
            class="add-file"
            type="file"
            accept=".pdf, .jpeg, .png, .xls, .xlsx, .ppt, .pptx"
            :name="FIELD_FILE_NAME"
            :disabled="filesCount >= FILES_LIMIT"
            @change="addFile($event.target.name, $event.target.files)"
          />
          <label
            for="ajout"
            class="add-file"
            :class="{ disabled: filesCount >= FILES_LIMIT }"
            :tabindex="filesCount >= FILES_LIMIT ? -1 : 0"
            @keydown.enter="inputFile.click()"
          >
            ajouter un fichier
          </label>
        </div>

        <div v-if="fileError" class="add-files-empty">
          <p class="txt-red" role="alert">
            Le fichier n'a pas pu être téléchargé.
            Veuillez vérifier le format et la taille du fichier.
          </p>
        </div>

        <div v-if="!filesCount" class="add-files-empty">
          <p>
            Vous avez la possibilité d’ajouter des pièces jointes à votre
            demande ci-dessus :
          </p>

          <ul class="add-file-list resetul">
            <li class="add-file-item">Le nombre maximal de pièces jointes est de 3</li>
            <li class="add-file-item">Les formats autorisés sont : pdf , jpeg, png, xls, xlsx, ppt, pptx</li>
            <li class="add-file-item">La taille maximale d'une pièce jointe est de 1mo</li>
          </ul>
        </div>

        <div v-else class="add-files-added">
          <ul class="resetul">
            <li v-for="(file, index) in formData.getAll(FIELD_FILE_NAME)" :key="`${file.name}${index}`">
              <div class="added-files-list">
                <span>{{ file.name }}</span>
                <hr class="sas-hr" />
                <button
                  class="sas-link-delete"
                  type="button"
                  :aria-label="`supprimer le fichier ${file.name}`"
                  @click="removeFile(index)"
                >
                  supprimer
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <div class="faq-btn">
        <button type="button" class="btn-highlight-outline" @click="backButton">Retour</button>
        <button type="submit" class="btn-highlight">envoyer</button>
      </div>
    </div>
  </VeeForm>
</template>

<script>
import { ref, onMounted, computed } from 'vue';

import {
  Form as VeeForm,
  Field,
  ErrorMessage,
} from 'vee-validate';
import * as yup from 'yup';
import isEmpty from 'lodash.isempty';

import Notification from '@/components/sharedComponents/Notification.component.vue';

import {
  useFaq,
  useUser,
} from '@/composables';

export default {
  components: {
    Notification,
    VeeForm,
    Field,
    ErrorMessage,
  },
  setup() {
    const { currentUser, getCurrentUser, isPscUser } = useUser();
    const {
      getThemes, getAllRoles, getHighestRole, uploadFormContact, getTerritories,
    } = useFaq();
    const inputFile = ref(null);
    const FIELD_FILE_NAME = 'attachment';
    const FILES_LIMIT = 3;
    const displayNotification = ref(false);
    const isPostSuccess = ref(false);
    const isPostError = ref(false);
    const errorsNotification = ref({});
    const territories = ref([]);
    const filesCount = ref();
    const roles = ref(getAllRoles());
    const formData = ref(new FormData());
    const themes = ref([]);
    const isLoaded = ref(false);
    const isConnected = ref(false);
    const fileError = ref(false);

    const schema = yup.object({
      role: yup.string().required(),
      topic: yup.string().required(),
      lastname: yup.string().required().max(30),
      firstname: yup.string().required().max(30),
      phone_number: yup.string().required().max(10).matches(/^((\+)33|0|0033)[1-9](\d{2}){4}$/g),
      email_address: yup.string().required().max(200).email(),
      territory: yup.string().required(),
      message: yup.string().required().max(2000),
    });

    const defaultValue = ref({
      email_address: '',
      firstname: '',
      lastname: '',
      role: '',
    });

    const notificationStatus = computed(() => ((!isEmpty(errorsNotification.value) || isPostError.value) ? 'error' : 'success'));
    const notificationMsg = computed(() => {
      if (
        isEmpty(errorsNotification.value)
        && isPostSuccess.value
        && !isPostError.value
      ) {
        return 'Votre message a bien été envoyé.';
      }

      if (isPostError.value) {
        return 'Une erreur s\'est produite lors de la tentative d\'envoi de votre message.';
      }

      if (!isEmpty(errorsNotification.value)) {
        return 'Veuillez renseigner les champs obligatoires';
      }

      return 'Une erreur s\'est produite lors de la tentative d\'envoi de votre message.';
    });

    onMounted(async () => {
      await getCurrentUser();
      let role = currentUser.value.getRoles();
      if (!role) role = 'anonymous';
      if (isPscUser()) role = [{ role: 'sas_effecteur' }];
      const themesModel = await getThemes(role, 'faq-contact-form');
      themes.value = themesModel.getThemes();
      territories.value = await getTerritories();

      if (currentUser.value?.firstname) {
        defaultValue.value.email_address = currentUser.value.email;
        defaultValue.value.firstname = currentUser.value.firstname;
        defaultValue.value.lastname = currentUser.value.lastname;
        defaultValue.value.role = getHighestRole(currentUser.value.roles, 'faq-contact-form');

        isConnected.value = true;
      } else if (isPscUser()) {
        defaultValue.value.role = getHighestRole(role, 'faq-contact-form');
      }

      isLoaded.value = true;
    });

    function onInvalidSubmit({ results }) {
      isPostError.value = false;
      isPostSuccess.value = false;
      displayNotification.value = false;
      errorsNotification.value = {};

      for (const [idField, val] of Object.entries(results)) {
        errorsNotification.value[idField] = getErrorMessage(val);
      }

      displayNotification.value = true;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /**
     * @description add a new file in the FormData
     * @param fieldName {string} is the input file name
     * @param file {file} is the file we'll add
     */
    function addFile(fieldName, file) {
      if (!file.length || file.length >= FILES_LIMIT) {
        fileError.value = true;
        return;
      }

      fileError.value = false;
      // append the files to FormData
      Array
        .from(Array(file.length).keys())
        // eslint-disable-next-line array-callback-return
        .map((x) => {
          const currentFile = file[x];
          // check if the file doesn't exceed the limit
          if (currentFile.size <= 1000000) {
            formData.value.append(fieldName, currentFile, currentFile.name);
          } else {
            fileError.value = true;
          }
        });

      // update files count
      filesCount.value = formData.value.getAll(FIELD_FILE_NAME).length;
    }

    /**
     * @description upload the form to the server
     * Catch error of success and display message in notification
     */
    async function uploadForm(schemaForm, { resetForm }) {
      try {
        isPostError.value = false;
        isPostSuccess.value = false;
        errorsNotification.value = {};
        displayNotification.value = false;
        const formDataFinal = getPayloadMapped(schemaForm);
        const postResponse = await uploadFormContact(formDataFinal);

        if (postResponse?.data) {
          resetForm();
          formData.value.getAll(FIELD_FILE_NAME).forEach((x, index) => removeFile(index));
          isPostSuccess.value = true;
        } else {
          isPostError.value = true;
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
        displayNotification.value = true;
      } catch (error) {
        console.error(error);
      }
    }

    /**
     * @description map the form JSON to a FormData with the files
     * @returns {FormData}
     */
    function getPayloadMapped(schemaForm) {
      // eslint-disable-next-line camelcase
      const formDataFinal = new FormData();

      // eslint-disable-next-line guard-for-in,no-restricted-syntax
      for (const key in schemaForm) {
        formDataFinal.append(key, schemaForm[key]);

        if (key === 'topic') {
          formDataFinal.append('reorientation', themes.value?.find((x) => x.thematic_key === schemaForm[key])?.reorientation);
        }
      }
      const formDataTemp = formData.value.getAll(FIELD_FILE_NAME);

      if (formDataTemp[0]) {
        formDataFinal.set('attachment', formDataTemp[0], formDataTemp[0].name);
      } else {
        formDataFinal.delete('attachment');
      }

      if (formDataTemp[1]) {
        formDataFinal.set('attachment_1', formDataTemp[1], formDataTemp[1].name);
      } else {
        formDataFinal.delete('attachment_1');
      }

      if (formDataTemp[2]) {
        formDataFinal.set('attachment_2', formDataTemp[2], formDataTemp[2].name);
      } else {
        formDataFinal.delete('attachment_2');
      }
      return formDataFinal;
    }

    /**
     *
     * @description remove a file in the FormData
     * @param index is the index of the file of a file input field
     */
    function removeFile(index) {
      const newFormData = new FormData();
      const oldFormData = formData.value.getAll(FIELD_FILE_NAME);
      Array
        .from(Array(oldFormData.length).keys())
        // eslint-disable-next-line array-callback-return
        .map((x) => {
          if (index !== x) {
            newFormData.append(FIELD_FILE_NAME, oldFormData[x], oldFormData[x].name);
          }
        });

      formData.value = newFormData;
      filesCount.value -= 1;
    }

    function backButton() {
      window.location.href = '/';
    }

    function getErrorMessage(val) {
      if (val.valid) {
        return '';
      }

      return val.errors?.find((err) => err.includes('required'))
      ? 'Ce champ est obligatoire'
      : 'Ce champ n\'est pas valide';
    }

    return {
      schema,
      displayNotification,
      onInvalidSubmit,
      errorsNotification,
      filesCount,
      formData,
      addFile,
      removeFile,
      FILES_LIMIT,
      FIELD_FILE_NAME,
      themes,
      defaultValue,
      isLoaded,
      isConnected,
      roles,
      uploadForm,
      territories,
      backButton,
      isPostSuccess,
      isPostError,
      isPscUser,
      notificationStatus,
      notificationMsg,
      inputFile,
      fileError,
    };
  },
};
</script>
