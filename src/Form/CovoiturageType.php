<?php 

namespace App\Form;

use App\Entity\Covoiturage;
use App\Entity\Voiture;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CovoiturageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $options['user'];

        $builder
            ->add('lieuDepart', TextType::class)
            ->add('lieuArrivee', TextType::class)
            ->add('dateDepart', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('prixPersonne', IntegerType::class)
            ->add('voiture', EntityType::class, [
                'class' => Voiture::class,
                'choices' => $user->getVoitures(),
                'choice_label' => function (Voiture $voiture) {
                    return $voiture->getMarque().' - '.$voiture->getModele().' ('.$voiture->getImmatriculation().')';
                },
                'placeholder' => 'Choisir un véhicule',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Covoiturage::class,
            'user' => null,
        ]);
    }
}
