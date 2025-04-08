<?php

namespace App\Form;

use App\Entity\Voiture;
use App\Form\PreferenceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('marque', TextType::class)
            ->add('placesDisponibles', IntegerType::class)
            ->add('preference', PreferenceType::class, [
                'label' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Voiture::class,
        ]);
    }
}
