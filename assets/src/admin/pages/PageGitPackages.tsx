import React from 'react';
import { useForm } from 'react-hook-form';
import { __, sprintf } from '@wordpress/i18n';
import { useSettingsForm, settingsKeys, useSettings } from '../settings';
import {
  Button,
  Card,
  Form,
  FormControls,
  FormElement,
  FormFeedback,
  InputCheckbox,
  InputRadio,
  InputSelect,
  InputText,
  NOTICE_TYPES,
  PageContent,
  ShadowBox,
} from '../theme';
import { VARS } from '../utils/constants';
import { IGitPackages, ISettings } from '../utils/types';
import RepositoryListView from './GitPackages/RepositoryListView';
import AddRepository from './GitPackages/add/AddRepository';
import styles from './PageGitPackages.css';
import TestForm from './TestForm';

const PageGitPackages = () => {
  const [addPackageModal, setAddPackageModal] = React.useState<boolean>(false);
  const { form, submit, error, loading } = useSettingsForm(
    settingsKeys.filter((key) => key.indexOf('git-packages') === 0)
  );

  const [repositories, setRepositories] = React.useState<IGitPackages>(
    VARS.gitPackages
  );

  return (
    <PageContent>
      {/*<TestForm />*/}
      <Card
        title={__('Git Repositories', 'shgi')}
        rightContent={
          repositories.length !== 0 && (
            <Button
              buttonType="primary"
              onClick={() => setAddPackageModal(true)}
            >
              {__('Repository hinzufügen', 'shgi')}
            </Button>
          )
        }
      >
        {repositories.length === 0 ? (
          <div className={styles.empty}>
            <p>{__('Es wurden noch keine Repositories gespeichert', 'shgi')}</p>
            <Button
              buttonType="primary"
              onClick={() => setAddPackageModal(true)}
            >
              {__('Repository hinzufügen', 'shgi')}
            </Button>
          </div>
        ) : (
          repositories.map((repo, i) => (
            <RepositoryListView
              key={i}
              repository={repo}
              setRepositories={setRepositories}
              className={styles.repository}
            />
          ))
        )}
      </Card>
      {addPackageModal && (
        <ShadowBox
          title={__('Repository hinzufügen', 'shgi')}
          close={() => setAddPackageModal(false)}
          size="medium"
        >
          <AddRepository
            repositoryKeys={repositories.map((r) => r.key)}
            setRepositories={setRepositories}
            onFinish={() => setAddPackageModal(false)}
          />
        </ShadowBox>
      )}
      <Card title={__('Zugriffskontrolle', 'shgi')} canToggleKey="git-packages">
        <Form onSubmit={submit}>
          <h3>Gitlab</h3>
          <FormElement
            form={form}
            name="git-packages-gitlab-token"
            Input={InputText}
            type="text"
            masked
          />
          <h3>Github</h3>
          <FormElement
            form={form}
            name="git-packages-github-token"
            Input={InputText}
            type="text"
            masked
          />
          <h3>Bitbucket</h3>
          <FormElement
            form={form}
            name="git-packages-bitbucket-user"
            Input={InputText}
          />
          <FormElement
            form={form}
            name="git-packages-bitbucket-token"
            Input={InputText}
            type="text"
            masked
          />
          {error !== '' && (
            <FormFeedback type={NOTICE_TYPES.ERROR} message={error} />
          )}
          <FormControls type="submit" loading={loading} />
        </Form>
      </Card>
    </PageContent>
  );
};

export default PageGitPackages;
