<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class CovoiturageSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('departure', TextType::class, [
                'label' => 'Départ',
                'attr' => ['placeholder' => 'Votre départ']
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'attr' => ['placeholder' => 'Votre destination']
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => ['placeholder' => 'Date du trajet']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
