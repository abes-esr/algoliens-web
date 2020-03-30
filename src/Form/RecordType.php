<?php

namespace App\Form;

use App\Entity\Record;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
            ->add('skipReason', ChoiceType::class, [
                'label' => "Notice non traitée pour le moment : ",
                'choices'  => [
                    'À reprendre document en main' => Record::SKIP_PHYSICAL_NEEDED,
                    'Proposer à nouveau dans cette interface plus tard' => Record::SKIP_OTHER_REASON,
                ],
                'mapped' => false
            ])
            ->add('id', HiddenType::class)
            ->add("validate", SubmitType::class,
                [
                    'label' => "Notice corrigée via WinibW ou Paprika !",
                    "attr" => [
                        "class" => "btn btn-success"
                    ]
                ])
            ->add("skip", SubmitType::class,
                [
                    'label' => "Enregistrer",
                    'attr' => [
                        "class" => "btn btn-outline-success"
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



class RecordType2 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        dd($options);
        $builder
            ->add('ppn')
            ->add('status')
            ->add('lastUpdate')
            ->add('locked')
            ->add('docTypeCode')
            ->add('docTypeLabel')
            ->add('marcBefore')
            ->add('marcAfter')
            ->add('urlCallType')
            ->add('winnie')
            ->add('rcrCreate')
            ->add("id")
        ;

        /*$session = new Session();
        $label_winnie = "";
        if ($session->get("winnie")) {
            $label_winnie = " ou dans WinibW";
        }
        $builder
            ->add('ppn', HiddenType::class)
            ->add('status', HiddenType::class)
            //->add('lastUpdate', HiddenType::class)
            //->add('locked', HiddenType::class)
            ->add('docTypeCode', HiddenType::class)
            ->add('docTypeLabel', HiddenType::class)
            ->add('rcrCreate', HiddenType::class)
            ->add("validate", SubmitType::class,
                [
                    'label' => "Notice corrigée via Paprika ".$label_winnie."!",
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
        ;*/
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Record::class,
        ]);
    }
}
