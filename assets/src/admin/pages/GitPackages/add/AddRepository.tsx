import React from 'react';
import {
  IGitPackageRaw,
  IGitPackages,
  IGitWordPressPackage,
} from '../../../utils/types';
import AddRepositoryForm from './AddRepositoryForm';
import CheckRepoForm from './CheckRepoForm';

const AddRepository: React.FC<{
  className?: string;
  repositoryKeys: Array<string>;
  setRepositories: (packages: IGitPackages) => void;
  onFinish: () => void;
}> = ({ className = '', repositoryKeys, setRepositories, onFinish = null }) => {
  const [repoData, setRepoData] = React.useState<IGitPackageRaw>(null);

  return (
    <div className={className}>
      {repoData ? (
        <AddRepositoryForm
          repository={repoData}
          setRepositories={setRepositories}
          onFinish={onFinish}
        />
      ) : (
        <CheckRepoForm setData={setRepoData} repositoryKeys={repositoryKeys} />
      )}
    </div>
  );
};

export default AddRepository;
