<?php

namespace App\Form;

use App\Entity\Record;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('ppn', HiddenType::class)
            //->add('status', HiddenType::class)
            // ->add('lastUpdate', HiddenType::class)
            // ->add('locked', HiddenType::class)
            //->add('docTypeCode', HiddenType::class)
            //->add('docTypeLabel', HiddenType::class)
            //->add('rcrCreate')
            ->add("validate", SubmitType::class,
                [
                    'label' => "Notice corrigée via WinibW ou Paprika !",
                    "attr" => [
                        "class" => "btn btn-success"
                    ]
                ])
            ->add("skip", SubmitType::class,
                [
                    'label' => "Je passe mon tour",
                    'attr' => [
                        "class" => "btn btn-link"
                    ]
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Record::class,
        ]);
    }
}
