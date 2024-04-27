export default class EffectorSettingsClass {
  constructor(effectorSettingsData) {
    this.id = effectorSettingsData.id || null;
    this.user_id = effectorSettingsData.user_id || null;
    this.editor_disabled = effectorSettingsData.editor_disabled || false;
    this.forfait_reo_enabled = effectorSettingsData.forfait_reo_enabled || false;
    this.participation_sas = effectorSettingsData.participation_sas || false;
    this.cpts_locations = effectorSettingsData.cpts_locations;
    this.has_software = effectorSettingsData.has_software || false;
    this.hours_available = effectorSettingsData.hours_available || true;
    this.participation_sas_via = effectorSettingsData.participation_sas_via || null;
    this.siret = effectorSettingsData.siret || null;
    this.structure_finess = effectorSettingsData.structure_finess || null;
}

  getSettingsData = () => ({
    id: this.id,
    user_id: this.user_id,
    editor_disabled: this.editor_disabled,
    forfait_reo_enabled: this.forfait_reo_enabled,
    participation_sas: this.participation_sas,
    cpts_locations: this.cpts_locations,
    has_software: this.participation_sas_via === 4 ? false : this.has_software,
    hours_available: this.participation_sas_via === 4 ? false : this.hours_available,
    participation_sas_via: this.participation_sas_via,
    siret: this.siret,
    structure_finess: this.structure_finess,
  });
}
