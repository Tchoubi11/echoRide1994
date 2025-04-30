<?php

// src/Form/ReservationValidationType.php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReservationValidationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('passengerFeedback', TextareaType::class, [
                'label' => 'Votre retour (facultatif)',
                'required' => false,
                'mapped' => false, 
            ])
            ->add('detailsProbleme', TextareaType::class, [
                'label' => 'Décrivez le problème rencontré',
                'required' => false,
            ])            
            ->add('passengerNote', IntegerType::class, [
                'label' => 'Note (1 à 5)',
                'required' => false,
                'mapped' => false, 
                'attr' => ['min' => 1, 'max' => 5],
                'constraints' => [
                    new Assert\Type('integer'),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 5,
                        'notInRangeMessage' => 'La note doit être entre 1 et 5.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}

