<?php



namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints as Assert;

class CovoiturageSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lieu_depart', TextType::class, [
                'label' => 'Départ',
                'attr' => ['placeholder' => 'Votre départ'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir un lieu de départ.']),
                ]
            ])
            ->add('lieu_arrivee', TextType::class, [
                'label' => 'Destination',
                'attr' => ['placeholder' => 'Votre destination'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir une destination.']),
                ]
            ])
            ->add('date_depart', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'input' => 'string',  
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d'), 
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir une date.']),
                    new Assert\GreaterThanOrEqual([
                        'value' => (new \DateTime())->format('Y-m-d'), 
                        'message' => 'La date doit être égale ou supérieure à aujourd\'hui.'
                    ])
                ]
            ]);

        if ($options['showAdvancedFilters']) {
            $builder
                ->add('is_eco', CheckboxType::class, [
                    'label' => 'Voyage écologique',
                    'required' => false,
                    'data' => false,
                ])
                ->add('max_price', NumberType::class, [
                    'label' => 'Prix maximum (crédits)',
                    'required' => false,
                ])
                ->add('max_duration', NumberType::class, [
                    'label' => 'Durée max (min)',
                    'required' => false,
                ])
                ->add('min_rating', NumberType::class, [
                    'required' => false,
                    'label' => 'Note minimale du conducteur',
                    'scale' => 1,  
                    'attr' => ['min' => 0, 'max' => 5]
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'showAdvancedFilters' => false, 
        ]);
    }
}