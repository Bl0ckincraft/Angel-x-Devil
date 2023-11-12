<?php

namespace App\Form;

use App\Utils\Mail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('to', CollectionType::class, [
                'label'        => 'À:',
                'entry_type'   => EmailType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required'     => true
            ])
            ->add('cc', CollectionType::class, [
                'label'        => 'CC:',
                'entry_type'   => EmailType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('cci', CollectionType::class, [
                'label'        => 'CCI:',
                'entry_type'   => EmailType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('subject', TextType::class, [
                'label'    => 'Objet:',
                'required' => true,
            ])
            ->add('textPlain', TextareaType::class, [
                'label'    => 'Message:',
                'required' => true,
            ])
            ->add('attachments', CollectionType::class, [
                'label' => 'Pièces Jointes:',
                'entry_type'   => FileType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('send', SubmitType::class, [
                'label' => 'Envoyer',
                'attr' => [
                    'class' => 'btn btn-success'
                ]
            ])
            ->add('draft', SubmitType::class, [
                'label' => 'Enregistrer le brouillon',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mail::class
        ]);
    }
}
