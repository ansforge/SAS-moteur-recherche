<template>
  <div class="editor-selector-container">
    <button
      id="select-editors-list"
      class="editor-selector"
      :class="showEditorslist ? 'select-opened' : 'closed'"
      :aria-expanded="showEditorslist"
      aria-controls="editors-list"
      type="button"
      @click.prevent="handleDisplay"
    >
      Choisissez des options
    </button>

    <div
      v-if="showEditorslist"
      v-click-outside="closeEditorList"
      class="editor-select"
    >
      <div v-if="editorsList.length" class="editor-autocomplete">
        <input type="search" autocomplete="off" v-model="editorsAutocomplete" placeholder="taper un nom" />
      </div>

      <ul
        id="editors-list"
        class="resetul editor-autocomplete-checkbox-list"
        role="region"
      >
        <li
          v-for="(editor, idx) in filterEditorsList"
          :key="`editor-checkbox-${idx}`"
          class="editor-checkboxs"
        >
          <input
            type="checkbox"
            :id="`editor-label-${idx}`"
            v-model="selectedEditors"
            :value="editor.id"
            class="editor-checkbox"
            @click="updateEditorsList(editor)"
          />
          <label :for="`editor-label-${idx}`">{{ editor.corporateName }}</label>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import {
 ref, computed, onMounted, watch,
} from 'vue';

export default {
  emits: ['update-editors-list'],
  props: {
    editorsList: {
      type: Array,
      default: () => [],
    },
    defaultEditors: {
      type: Array,
      default: () => [],
    },
  },
  setup(props, { emit }) {
    const editorsAutocomplete = ref('');
    const selectedEditors = ref([]);

    // autocomplete feature
    const filterEditorsList = computed(() => {
      if (editorsAutocomplete.value) {
        return props.editorsList.filter((editor) => editor.corporateName.toLowerCase().includes(editorsAutocomplete.value.toLowerCase()));
      }
      return props.editorsList;
    });

    const showEditorslist = ref(false);

    watch(() => props.defaultEditors, () => {
        // to select editors from API data
        selectedEditors.value = props.defaultEditors;
      });

    onMounted(() => {
      selectedEditors.value = props.defaultEditors;
    });

    function updateEditorsList(editor) {
      emit('update-editors-list', {
        selectedEditors: editor,
        isChecked: selectedEditors.value.includes(editor.id),
      });
    }

    function handleDisplay(e) {
      e.stopPropagation();
      showEditorslist.value = !showEditorslist.value;
    }

    /**
     * on click outside of editor's list, hide editor's list && reset autocomplete value
     */
    function closeEditorList() {
      showEditorslist.value = false;
      editorsAutocomplete.value = '';
    }

    return {
      editorsAutocomplete,
      selectedEditors,
      filterEditorsList,
      showEditorslist,
      updateEditorsList,
      closeEditorList,
      handleDisplay,
    };
  },
};
</script>
