<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationValidationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isValidatedByPassenger', CheckboxType::class, [
                'label' => 'Valider la réservation ?',
                'required' => false,
            ])
            ->add('passengerFeedback', TextareaType::class, [
                'label' => 'Votre retour (facultatif)',
                'required' => false,
            ])
            ->add('passengerNote', IntegerType::class, [
                'label' => 'Note (1 à 5)',
                'required' => false,
                'attr' => ['min' => 1, 'max' => 5],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
