<?php
/**
 * File containing the User controller class
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace eZ\Publish\Core\REST\Server\Controller;
use eZ\Publish\Core\REST\Common\UrlHandler;
use eZ\Publish\Core\REST\Common\Message;
use eZ\Publish\Core\REST\Common\Input;
use eZ\Publish\Core\REST\Server\Values;
use eZ\Publish\Core\REST\Server\Exceptions;

use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Repository;

use Qafoo\RMF;

/**
 * User controller
 */
class User
{
    /**
     * Input dispatcher
     *
     * @var \eZ\Publish\Core\REST\Common\Input\Dispatcher
     */
    protected $inputDispatcher;

    /**
     * URL handler
     *
     * @var \eZ\Publish\Core\REST\Common\UrlHandler
     */
    protected $urlHandler;

    /**
     * User service
     *
     * @var \eZ\Publish\API\Repository\UserService
     */
    protected $userService;

    /**
     * Location service
     *
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * Repository
     *
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * Construct controller
     *
     * @param \eZ\Publish\Core\REST\Common\Input\Dispatcher $inputDispatcher
     * @param \eZ\Publish\Core\REST\Common\UrlHandler $urlHandler
     * @param \eZ\Publish\API\Repository\UserService $userService
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     * @param \eZ\Publish\API\Repository\Repository $repository
     */
    public function __construct( Input\Dispatcher $inputDispatcher, UrlHandler $urlHandler, UserService $userService, LocationService $locationService, Repository $repository )
    {
        $this->inputDispatcher = $inputDispatcher;
        $this->urlHandler = $urlHandler;
        $this->userService = $userService;
        $this->locationService = $locationService;
        $this->repository = $repository;
    }

    /**
     * Redirects to the root user group
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\PermanentRedirect
     */
    public function loadRootUserGroup( RMF\Request $request )
    {
        //@todo Replace hardcoded value with one loaded from settings
        return new Values\PermanentRedirect(
            $this->urlHandler->generate( 'group', array( 'group' => '/1/5' ) ),
            'UserGroup'
        );
    }

    /**
     * Loads a user group for the given path
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\RestUserGroup
     */
    public function loadUserGroup( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'group', $request->path );

        $userGroupLocation = $this->locationService->loadLocation(
            $this->extractLocationIdFromPath( $urlValues['group'] )
        );

        $userGroup = $this->userService->loadUserGroup(
            $userGroupLocation->contentId
        );

        return new Values\RestUserGroup(
            $userGroup,
            $userGroup->getVersionInfo()->getContentInfo(),
            $userGroupLocation
        );
    }

    /**
     * Loads a user for the given ID
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\RestUser
     */
    public function loadUser( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'user', $request->path );

        $user = $this->userService->loadUser(
            $urlValues['user']
        );

        $userContentInfo = $user->getVersionInfo()->getContentInfo();
        $userMainLocation = $this->locationService->loadLocation( $userContentInfo->mainLocationId );

        return new Values\RestUser(
            $user,
            $userContentInfo,
            $userMainLocation
        );
    }

    /**
     * Create a new user group under the given parent
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\CreatedUserGroup
     */
    public function createUserGroup( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'groupSubgroups', $request->path );

        $userGroupLocation = $this->locationService->loadLocation(
            $this->extractLocationIdFromPath( $urlValues['group'] )
        );

        $createdUserGroup = $this->userService->createUserGroup(
            $this->inputDispatcher->parse(
                new Message(
                    array( 'Content-Type' => $request->contentType ),
                    $request->body
                )
            ),
            $this->userService->loadUserGroup(
                $userGroupLocation->contentId
            )
        );

        $createdContentInfo = $createdUserGroup->getVersionInfo()->getContentInfo();
        $createdLocation = $this->locationService->loadLocation( $createdContentInfo->mainLocationId );
        return new Values\CreatedUserGroup(
            array(
                'userGroup' => new Values\RestUserGroup(
                    $createdUserGroup,
                    $createdContentInfo,
                    $createdLocation
                )
            )
        );
    }

    /**
     * Given user group is deleted
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\ResourceDeleted
     */
    public function deleteUserGroup( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'group', $request->path );

        $userGroupLocation = $this->locationService->loadLocation(
            $this->extractLocationIdFromPath( $urlValues['group'] )
        );

        $userGroup = $this->userService->loadUserGroup(
            $userGroupLocation->contentId
        );

        $this->userService->deleteUserGroup( $userGroup );

        return new Values\ResourceDeleted();
    }

    /**
     * Given user is deleted
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\ResourceDeleted
     */
    public function deleteUser( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'user', $request->path );

        $user = $this->userService->loadUser(
            $urlValues['user']
        );

        if ( $user->id == $this->repository->getCurrentUser()->id )
        {
            throw new Exceptions\ForbiddenException( "Currently authenticated user cannot be deleted" );
        }

        $this->userService->deleteUser( $user );

        return new Values\ResourceDeleted();
    }

    /**
     * loads the users of the group with the given path
     *
     * @param RMF\Request $request
     * @return \eZ\Publish\Core\REST\Server\Values\UserList
     */
    public function loadUsersFromGroup( RMF\Request $request )
    {
        $urlValues = $this->urlHandler->parse( 'groupUsers', $request->path );

        $userGroupLocation = $this->locationService->loadLocation(
            $this->extractLocationIdFromPath( $urlValues['group'] )
        );

        $userGroup = $this->userService->loadUserGroup(
            $userGroupLocation->contentId
        );

        $users = $this->userService->loadUsersOfUserGroup( $userGroup );

        if ( $this->getMediaType( $request ) === 'application/vnd.ez.api.userlist' )
        {
            $restUsers = array();
            foreach ( $users as $user )
            {
                $userContentInfo = $user->getVersionInfo()->getContentInfo();
                $userLocation = $this->locationService->loadLocation( $userContentInfo->mainLocationId );
                $restUsers[] = new Values\RestUser( $user, $userContentInfo, $userLocation );
            }
            return new Values\UserList( $restUsers, $request->path );
        }

        return new Values\UserRefList( $users, $request->path );
    }

    /**
     * Extracts and returns an item id from a path, e.g. /1/2/58 => 58
     *
     * @param string $path
     * @return mixed
     */
    private function extractLocationIdFromPath( $path )
    {
        $pathParts = explode( '/', $path );
        return array_pop( $pathParts );
    }

    /**
     * Extracts the requested media type from $request
     *
     * @param RMF\Request $request
     * @return string
     */
    protected function getMediaType( RMF\Request $request )
    {
        foreach ( $request->mimetype as $mimeType )
        {
            if ( preg_match( '(^([a-z0-9-/.]+)\+.*$)', $mimeType['value'], $matches ) )
            {
                return $matches[1];
            }
        }
        return 'unknown/unknown';
    }
}
