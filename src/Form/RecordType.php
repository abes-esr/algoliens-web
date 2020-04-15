<?php

namespace App\Form;

use App\Entity\Record;
use App\Entity\SkipReason;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $record = $builder->getData();
        $builder
            /*->add('ppn')
            ->add('status')
            ->add('lastUpdate')
            ->add('locked')
            ->add('docTypeCode')
            ->add('docTypeLabel')
            ->add('marcBefore')
            ->add('marcAfter')
            ->add('urlCallType')
            ->add('winnie')
            ->add('rcrCreate')*/
            ->add('id', HiddenType::class)
            ->add("validate", SubmitType::class,
                [
                    'label' => "Enregistrer la correction",
                    "attr" => [
                        "class" => "btn btn-success"
                    ]
                ])
            ->add("skip", SubmitType::class,
                [
                    'label' => "Enregistrer l'état « reprise nécessaire »",
                    'attr' => [
                        "class" => "btn btn-success"
                    ]
                ])
            ->add("comment", TextareaType::class,
                [
                    'label' => "Commentaire : ",
                    'required' => false
                ]
            );

        $skip_reasons = $options["skip_reasons"];
        if (sizeof($skip_reasons) == 0) {
            $skip_reasons = $record->getRcrCreate()->getIln()->getSkipReasons();
        }

        $skip_reason_default = $options["skip_reason_default"];
        if (is_null($skip_reason_default)) {
            $skip_reason_default = $record->getRcrCreate()->getIln()->getDefaultSkipReason();
        }

        if (sizeof($skip_reasons) > 0) {
            $builder->add('skipReason', EntityType::class, [
                'class' => SkipReason::class,
                'label' => "Raison du non traitement : ",
                'choices' => $skip_reasons,
                'expanded' => true,
                'data' => $skip_reason_default
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Record::class,
            'skip_reasons' => [],
            'skip_reason_default' => null
        ]);
    }
}
