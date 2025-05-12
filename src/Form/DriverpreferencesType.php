<?php


namespace App\Form;

use App\Entity\DriverPreferences;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DriverpreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fumeur', CheckboxType::class, [
                'label' => 'Fumeur',
                'required' => false,
            ])
            ->add('animaux', CheckboxType::class, [
                'label' => 'Animaux',
                'required' => false,
            ])
            ->add('autres', TextType::class, [
                'label' => 'Autres préférences (facultatif)',
                'required' => false,
                'attr' => ['placeholder' => 'Indiquez d\'autres préférences'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DriverPreferences::class,
        ]);
    }
}
