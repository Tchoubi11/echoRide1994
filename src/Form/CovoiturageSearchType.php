<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints as Assert;

class CovoiturageSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('departure', TextType::class, [
                'label' => 'Départ',
                'attr' => ['placeholder' => 'Votre départ'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir un lieu de départ.']),
                    new Assert\Length([
                        'max' => 255, 
                        'maxMessage' => 'Le lieu de départ ne doit pas dépasser {{ limit }} caractères.'
                    ]),
                ]
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'attr' => ['placeholder' => 'Votre destination'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir une destination.']),
                    new Assert\Length([
                        'max' => 255, 
                        'maxMessage' => 'La destination ne doit pas dépasser {{ limit }} caractères.'
                    ]),
                ]
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => ['placeholder' => 'Date du trajet'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir une date.']),
                    new Assert\Type([
                        'type' => \DateTime::class, 
                        'message' => 'La date doit être valide.'
                    ]),
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
