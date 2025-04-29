<?php

namespace App\Form;

use App\Entity\Covoiturage;
use App\Entity\Voiture;
use App\Repository\VoitureRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class CovoiturageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
    
        $builder
            ->add('lieuDepart', TextType::class)
            ->add('lieuArrivee', TextType::class)
            ->add('dateDepart', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('nbPlace', IntegerType::class, [
                'label' => 'Nombre de places',
                'required' => true,
                'mapped' => true,
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le nombre de places ne peut pas être nul.']),
                    new Assert\Positive(['message' => 'Le nombre de places doit être positif.'])
                ],
            ])
            ->add('dateArrivee', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false, // DateArrivee est maintenant optionnelle
            ])
            ->add('prixPersonne', IntegerType::class, [
                'constraints' => [
                    new Assert\Range([
                        'min' => 1, 
                        'minMessage' => 'Le prix ne peut pas être inférieur à 1',
                    ])
                ]
            ])
            ->add('voiture', EntityType::class, [
                'class' => Voiture::class,
                'query_builder' => function (VoitureRepository $vr) use ($user) {
                    return $vr->createQueryBuilder('v')
                        ->where('v.utilisateur = :user')
                        ->setParameter('user', $user)
                        ->orderBy('v.modele', 'ASC');
                },
                'choice_label' => function (Voiture $voiture) {
                    return ($voiture->getMarque()?->getLibelle() ?? 'Sans marque') .
                        ' - ' . $voiture->getModele() .
                        ' (' . $voiture->getImmatriculation() . ')';
                },
                'placeholder' => 'Choisir un véhicule',
            ])
            ->add('is_eco', CheckboxType::class, [
                'label' => 'Eco-friendly',
                'required' => false,  // facultatif, selon si vous voulez le rendre obligatoire
            ]);
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Covoiturage::class,
            'user' => null,
        ]);
    }
}
