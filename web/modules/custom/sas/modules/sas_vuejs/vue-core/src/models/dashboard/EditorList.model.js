export default class EditorListClass {
  constructor(editorList) {
    this.editorList = editorList.map((editor) => ({ ...editor, ...{ isDisabled: false } }));
  }

  getEditorNameList = () => this.editorList;
}
