# -*- coding: utf-8 -*-
import csv
import re

csv_arr = []
csv_dict = {}

with open('questionnaire.csv', 'r') as datas:
    datas_reader = csv.DictReader(datas)

    with open('questionnaire_norm.csv', 'w') as fichier_csv:
        fieldnames = ['nom', 'email', 'code_postal', 'adresse',
                      'web1', 'web2', 'facebook', 'instagram',
                      'twitter', 'date_creation', 'activites', 'membre']

        datas_writer = csv.DictWriter(fichier_csv, fieldnames=fieldnames)

        datas_writer.writeheader()

        for line in datas_reader:

            # Vérifier si certaines données essentielles sont manquantes,
            # dans cette hypothèse, la ligne entière est ignorée
            if (line['nom']):

                # L'association accepte d'être sur le site de cartographie.
                # Les associations qui refusent ne sont pas conservées.
                if (line['cartographie'] == 'OUI'):

                    csv_dict['nom'] = line['nom']

                    csv_dict['email'] = line['email']

                    # L'adresse : supprimer le code postal.
                    csv_dict['adresse'] = re.sub(
                        r'(\d{5})',
                        '',
                        line['adresse']
                    ).lstrip().rstrip()

                    # Le code postal est inséré dans une colonne spécifique
                    code_postal = re.search(r'(\d{5})', line['adresse'])

                    if code_postal is None:
                        csv_dict['code_postal'] = ''
                    else:
                        csv_dict['code_postal'] = code_postal.group(0)

                    # Les sites doivent être traités à la main
                    # car les données sont trop hétérogènes.
                    # csv_dict['sites'] = line['sites']

                    csv_dict['web1'] = line['web1']
                    csv_dict['web2'] = line['web2']
                    csv_dict['facebook'] = line['facebook']
                    csv_dict['instagram'] = line['instagram']
                    csv_dict['twitter'] = line['twitter']

                    # Date de création de l'association
                    csv_dict['date_creation'] = line['date_creation']

                    # Activités
                    csv_dict['activites'] = line['activites']

                    # L'association est-elle membre ?
                    membre = line['membre'].lower()

                    if membre == '':
                        membre = 'non'

                    membre = re.search(r'(non).*', line['membre'], re.I)

                    if membre is None:
                        csv_dict['membre'] = line['membre']
                    else:
                        csv_dict['membre'] = 'non'

                    datas_writer.writerow(csv_dict)

                # Ajouter les données au tableau
                # csv_arr.append(csv_dict)
