<?php


namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class)
            ->add('prenom', TextType::class)
            ->add('email', TextType::class)
            ->add('telephone', TextType::class)
            ->add('adresse', TextType::class)
            ->add('date_naissance', DateType::class, [
                   'widget' => 'single_text',
                   'html5' => true,
                   'input' => 'datetime', 
                   'format' => 'yyyy-MM-dd',
                   'label' => 'Date de naissance',
            ])->add('type_utilisateur', ChoiceType::class, [
        'label' => 'Vous êtes :',
        'choices' => [
            'Passager' => 'passager',
            'Chauffeur' => 'chauffeur',
            'Les deux' => 'les_deux',
        ],
        'expanded' => true,
        'multiple' => false,
    ])

            ->add('pseudo', TextType::class, [ 
                'label' => 'Pseudo',
                'required' => true,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,  
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
