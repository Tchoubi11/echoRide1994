<?php

// src/Form/VoitureType.php

namespace App\Form;

use App\Entity\Voiture;
use App\Entity\Marque;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

class VoitureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('immatriculation', TextType::class)
            ->add('datePremiereImmatriculation', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('modele', TextType::class)
            ->add('couleur', TextType::class)
            ->add('marque', EntityType::class, [
                'class' => Marque::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Choisir une marque',
                'required' => true,
            ])
            ->add('energie', ChoiceType::class, [
                'choices' => [
                    'Essence' => 'essence',
                    'Diesel' => 'diesel',
                    'Electrique' => 'electrique',
                    'Hybride' => 'hybride',
                ],
                'label' => 'Type d\'énergie',  
                'required' => true,  
            ])
            ->add('placesDisponibles', IntegerType::class)->add('placesDisponibles', IntegerType::class, [
                'label' => 'Nombre de places disponibles',
                'required' => true,
                'data' => 1,
                'attr' => [
                    'min' => 1,
                    'step' => 1,
                ],
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le nombre de places ne peut pas être nul.']),
                    new Assert\Range([
                        'min' => 1,
                        'minMessage' => 'Le nombre de places doit être au minimum {{ limit }}.',
                    ]),
                ],
            ]);
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Voiture::class,
            'user' => null,
        ]);
    }
}
