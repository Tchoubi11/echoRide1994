<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Preference;


class PreferenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fumeur', ChoiceType::class, [
                'label' => 'Fumeur autorisé ?',
                'choices' => ['Oui' => true, 'Non' => false],
                'expanded' => true,
            ])
            ->add('animaux', ChoiceType::class, [
                'label' => 'Animaux autorisés ?',
                'choices' => ['Oui' => true, 'Non' => false],
                'expanded' => true,
            ])
            ->add('autres', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'required' => false,
                'label' => 'Autres préférences',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Preference::class,
        ]);
    }
}
