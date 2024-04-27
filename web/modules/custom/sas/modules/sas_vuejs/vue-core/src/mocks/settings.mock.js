const popinSnp = {
    id: 3,
    group_name: 'SNP',
    name: 'popin_snp',
    value: {
      group1: {
        titre: 'AJOUTER OU MODIFIER DES DISPONIBILITÉS',
        sous_titre: 'Vous pouvez superposer vos disponibilités sans rendez-vous, sur rendez-vous à domicile.\r\nLorsque vous êtes en congés, pensez à supprimer vos disponibilités.',
        nom_snp: 'Disponibilités',
      },
      group2: {
        date: 'Date',
        de: 'De',
        a: 'A',
      },
      group3: {
        consultation: 'Je souhaite rendre visible un créneau disponible',
        rendez_vous: 'Je peux recevoir\r\n[snp:popin_case] patients sans RDV\r\nsur cette plage horaire',
      },
      group4: {
        titre_type_consultation: 'Type de consultation',
        cabinet: 'Consultation en cabinet',
        teleconsultation: 'Téléconsultation',
        domicile: 'Visite à domicile',
        initial_cabinet: 'C',
        initial_teleconsultation: 'T',
        initial_domicile: 'D',
      },
      group5: {
        titre_recurrence: 'Créer une récurrence hebdomadaire',
        jours: 'Chaque',
      },
    },
};

export default {
  popinSnp,
};
