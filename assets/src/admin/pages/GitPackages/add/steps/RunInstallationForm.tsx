import React from 'react';
import { useForm } from 'react-hook-form';
import { __ } from '@wordpress/i18n';
import {
  Form,
  FormControls,
  FormElement,
  FormFeedback,
  InputSelect,
  NOTICE_TYPES,
} from '../../../../theme';
import { VARS } from '../../../../utils/constants';
import { AddRepositoryFormPropsI } from '../AddRepository';

const RunInstallationForm: React.FC<AddRepositoryFormPropsI> = ({
  promise,
  submit,
  repoData,
  wpPackage,
  className = '',
}) => {
  const [loading, setLoading] = React.useState<boolean>(false);
  const [error, setError] = React.useState<string>('');

  const form = useForm<{ savePluginAs: string }>({
    defaultValues: {
      savePluginAs: '',
    },
  });

  const showMustUseForm = !VARS.mustUsePlugins || wpPackage?.type !== 'plugin';

  const install = (savePluginAsMU: boolean = false) => {
    setLoading(true);
    promise(savePluginAsMU)
      .then()
      .catch((e) => setError(e))
      .finally(() => setLoading(false));
  };

  React.useEffect(() => {
    showMustUseForm && install(false);
  }, []);

  return (
    <Form
      onSubmit={form.handleSubmit((data) =>
        install(data.savePluginAs === 'mustUse')
      )}
      className={className}
    >
      <FormElement
        form={form}
        name="savePluginAs"
        label={__('Save plugin as', 'shgi')}
        Input={InputSelect}
        options={{
          ['']: __('select..', 'shgi'),
          normal: __('Normal Plugin', 'shgi'),
          mustUse: __('Must Use Plugin', 'shgi'),
        }}
      />
      {error !== '' && (
        <FormFeedback type={NOTICE_TYPES.ERROR}>{error}</FormFeedback>
      )}
      <FormControls
        type="submit"
        loading={loading}
        value={submit}
        align="right"
      />
    </Form>
  );
};

export default RunInstallationForm;
