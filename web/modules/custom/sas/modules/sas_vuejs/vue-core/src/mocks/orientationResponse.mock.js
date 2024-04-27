export default {
  id: 167,
  regulator: {
    id: 1,
    email: 'sas-regulateur-osnp-test@mail.test',
    lastname: 'Doe',
    firstname: 'Jane',
    territory: [
      {
        id: 11,
        name: 'SAS-974 La Réunion',
      },
    ],
    county: 'La Réunion',
    county_number: '974',
  },
  recipient: {
    id: 167,
    name: 'Dr Gautier Benoit',
    type: 1,
    address: '169 Avenue Aristide Briand',
    county: 'Val-de-Marne',
    county_number: '94',
    effector_rpps: '10003931184',
    effector_adeli: null,
    effector_speciality: 'Médecine générale',
    effector_territory: null,
    effector_is_sas: true,
    structure_finess: null,
    structure_siret: null,
    structure_type: '',
  },
  orientation_date: '2022-05-20T15:10:54+02:00',
  orientation_type: 1,
  orientation_status: 0,
  slot_date: '2022-05-20T19:00:00+02:00',
  slot: {
    id: 729,
    schedule: {
      id: 157,
      organization: {
        id: 210,
        rpps_rang: '1000164397',
        finess: null,
        siret: null,
        ror: null,
      },
      practitioner: {
        id: 148,
        pro_id: '10003931184',
      },
      slots: [
        {
          id: 730,
          type: 'recurring',
          date: '2022-03-14T19:00:00+00:00',
          start_hours: '1900',
          end_hours: '2000',
          day: 1,
          modalities: [
            'physical',
            'teleconsultation',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: 4,
        },
        {
          id: 731,
          type: 'recurring',
          date: '2022-03-15T19:00:00+00:00',
          start_hours: '1900',
          end_hours: '2000',
          day: 2,
          modalities: [
            'physical',
            'teleconsultation',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: 4,
        },
        {
          id: 732,
          type: 'recurring',
          date: '2022-03-16T19:00:00+00:00',
          start_hours: '1900',
          end_hours: '2000',
          day: 3,
          modalities: [
            'physical',
            'teleconsultation',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: 4,
        },
        {
          id: 733,
          type: 'recurring',
          date: '2022-03-17T19:00:00+00:00',
          start_hours: '1900',
          end_hours: '2000',
          day: 4,
          modalities: [
            'physical',
            'teleconsultation',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: 4,
        },
        {
          id: 734,
          type: 'recurring',
          date: '2022-03-12T19:00:00+00:00',
          start_hours: '1900',
          end_hours: '2000',
          day: 6,
          modalities: [
            'physical',
            'teleconsultation',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: 4,
        },
        {
          id: 888,
          type: 'recurring',
          date: '2022-05-17T10:00:00+00:00',
          start_hours: '1000',
          end_hours: '1015',
          day: 2,
          modalities: [
            'physical',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: -1,
        },
        {
          id: 889,
          type: 'recurring',
          date: '2022-05-23T10:00:00+00:00',
          start_hours: '1000',
          end_hours: '1015',
          day: 1,
          modalities: [
            'physical',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: -1,
        },
        {
          id: 890,
          type: 'recurring',
          date: '2022-05-18T10:00:00+00:00',
          start_hours: '1000',
          end_hours: '1015',
          day: 3,
          modalities: [
            'physical',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: -1,
        },
        {
          id: 891,
          type: 'recurring',
          date: '2022-05-19T10:00:00+00:00',
          start_hours: '1000',
          end_hours: '1015',
          day: 4,
          modalities: [
            'physical',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: -1,
        },
        {
          id: 892,
          type: 'recurring',
          date: '2022-05-20T10:00:00+00:00',
          start_hours: '1000',
          end_hours: '1015',
          day: 5,
          modalities: [
            'physical',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: -1,
        },
        {
          id: 893,
          type: 'recurring',
          date: '2022-05-21T10:00:00+00:00',
          start_hours: '1000',
          end_hours: '1015',
          day: 6,
          modalities: [
            'physical',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: -1,
        },
        {
          id: 894,
          type: 'recurring',
          date: '2022-05-22T10:00:00+00:00',
          start_hours: '1000',
          end_hours: '1015',
          day: 7,
          modalities: [
            'physical',
          ],
          real_date: null,
          orientation_count: null,
          exceptions: [],
          max_patients: -1,
        },
      ],
    },
    type: 'recurring',
    date: '2022-03-11T19:00:00+00:00',
    start_hours: '1900',
    end_hours: '2000',
    day: 5,
    modalities: [
      'physical',
      'teleconsultation',
    ],
    real_date: null,
    orientation_count: 0,
    exceptions: [],
    max_patients: -1,
  },
  slot_modality: [
    'physical',
    'teleconsultation',
  ],
};
