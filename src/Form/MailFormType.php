<?php

namespace App\Form;

use App\Entity\MailDraft;
use App\Utils\Mail;
use App\Utils\MailFormData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;

class MailFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('toList', CollectionType::class, [
                'label'        => 'À:',
                'entry_type'   => EmailType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required'     => true,
                'constraints' => [
                    new Count(min: 1, minMessage: 'Vous devez entrez au moins un destinataire.')
                ]
            ])
            ->add('cc', CollectionType::class, [
                'label'        => 'CC:',
                'entry_type'   => EmailType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'required' => false,
                    'label' => '',
                ],
            ])
            ->add('cci', CollectionType::class, [
                'label'        => 'CCI:',
                'entry_type'   => EmailType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'required' => false,
                    'label' => '',
                ],
            ])
            ->add('subject', TextType::class, [
                'label'    => 'Objet:',
                'required' => true,
            ])
            ->add('message', TextareaType::class, [
                'label'    => 'Message:',
                'required' => true,
                'attr' => [
                    'style' => 'height: 440px'
                ]
            ])
            ->add('attachments', CollectionType::class, [
                'label' => 'PJ:',
                'entry_type'   => FileType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'required' => false,
                    'label' => '',
                    'constraints' => [
                        new File([
                            'maxSize' => '5M'
                        ])
                    ]
                ],
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => [
                    'class' => 'btn btn-success'
                ]
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
                'data_class' => MailFormData::class
        ]);
    }
}
